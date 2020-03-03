/* $begin FML server main */
/*
 * FML server由csapp教材中的tiny服务器扩展而来，可支持POST方法，可支持php，可多线程运行，同时增加了一些对异常的处理。
 */
#include "csapp.h"
#include "fastcgi.h"

void doit(int fd);
void read_requesthdrs(rio_t *rp,char* method,char* args,int* postlen,char* contenttype,char* cookie);
int parse_uri(char *uri, char *filename, char *cgiargs,char* method);
void serve_static(int fd, char *filename, int filesize);
void get_filetype(char *filename, char *filetype);
void serve_dynamic(int fd, char* method, char *filename, char *cgiargs, int postlen,char* contenttype,char* cookie);
void clienterror(int fd, char *cause, char *errnum, 
		 char *shortmsg, char *longmsg);
void* thread(void* vargp);

int main(int argc, char **argv) 
{
    int listenfd, *connfdp;
    socklen_t clientlen;
    struct sockaddr_storage clientaddr;
    pthread_t tid;

    /* Check command line args */
    if (argc != 2) {
	fprintf(stderr, "usage: %s <port>\n", argv[0]);
	exit(1);
    }

    listenfd = Open_listenfd(argv[1]);
    Signal(SIGPIPE,SIG_IGN);
    while (1) {
		clientlen = sizeof(clientaddr);
		connfdp=Malloc(sizeof(int));
		*connfdp = Accept(listenfd, (SA *)&clientaddr, &clientlen);
		Pthread_create(&tid,NULL,thread,connfdp);
    }
}
/* $end FML server main */

/*
 * doit - handle one HTTP request/response transaction
 */
/* $begin doit */
void doit(int fd) 
{
    int is_static;
    struct stat sbuf;
    char buf[MAXLINE], method[MAXLINE], uri[MAXLINE], version[MAXLINE],contenttype[MAXLINE],cookie[MAXLINE];
    char filename[MAXLINE], cgiargs[MAXLINE];
    rio_t rio;

    /* Read request line and headers */
    Rio_readinitb(&rio, fd);
    if (!Rio_readlineb(&rio, buf, MAXLINE))  //line:netp:doit:readrequest
        return;
    printf("%s", buf);
    sscanf(buf, "%s %s %s", method, uri, version);       //line:netp:doit:parserequest
    if (strcasecmp(method, "GET") && strcasecmp(method,"POST")) {                     //line:netp:doit:beginrequesterr
        clienterror(fd, method, "501", "Not Implemented",
                    "FML server does not implement this method");
        return;
    }                                                   //line:netp:doit:endrequesterr
	int postlen;
    read_requesthdrs(&rio,method,cgiargs,&postlen,contenttype,cookie);                              //line:netp:doit:readrequesthdrs

    /* Parse URI from GET request */
    is_static = parse_uri(uri, filename, cgiargs,method);       //line:netp:doit:staticcheck
    if (stat(filename, &sbuf) < 0) {                     //line:netp:doit:beginnotfound
	clienterror(fd, filename, "404", "Not found",
		    "FML server couldn't find this file");
	return;
    }                                                    //line:netp:doit:endnotfound

    if (is_static) { /* Serve static content */          
	if (!(S_ISREG(sbuf.st_mode)) || !(S_IRUSR & sbuf.st_mode)) { //line:netp:doit:readable
	    clienterror(fd, filename, "403", "Forbidden",
			"FML server couldn't read the file");
	    return;
	}
	serve_static(fd, filename, sbuf.st_size);        //line:netp:doit:servestatic
    }
    else { /* Serve dynamic content */
	serve_dynamic(fd, method,filename, cgiargs,postlen,contenttype,cookie);            //line:netp:doit:servedynamic
    }
}
/* $end doit */

/*
 * read_requesthdrs - read HTTP request headers
 */
/* $begin read_requesthdrs */
void read_requesthdrs(rio_t *rp,char* method,char* args,int* postlen,char* contenttype,char* cookie) 
{
    char buf[MAXLINE];

    Rio_readlineb(rp, buf, MAXLINE);
    printf("%s", buf);
    while(strcmp(buf, "\r\n")) {          //line:netp:readhdrs:checkterm
	Rio_readlineb(rp, buf, MAXLINE);
	if(strcasecmp(method,"POST")==0 && strstr(buf, "Content-Length")){
		char* p=index(buf,':');
		*postlen=atoi(p+2);
	}
	if(strstr(buf, "Cookie")){
		char* p=index(buf,':');
		char* p2=index(buf,'\r');
		*p2='\0';
		strcpy(cookie,p+2);
	}
	if(strcasecmp(method,"POST")==0 && strstr(buf, "Content-Type")){
		char* p=index(buf,':');
		char* p2=index(buf,'\r');
		*p2='\0';
		strcpy(contenttype,p+2);
	}
    }
    if(strcasecmp(method,"POST")==0)
    Rio_readnb(rp,args,*postlen);
    return;
}
/* $end read_requesthdrs */

/*
 * parse_uri - parse URI into filename and CGI args
 *             return 0 if dynamic content, 1 if static
 */
/* $begin parse_uri */
int parse_uri(char *uri, char *filename, char *cgiargs, char* method) 
{
    char *ptr;

    if (!strstr(uri, ".php")) {  /* Static content */ //这里FML server只处理.php的动态内容
	strcpy(cgiargs, "");                             //line:netp:parseuri:clearcgi
	strcpy(filename, ".");                           //line:netp:parseuri:beginconvert1
	strcat(filename, uri);                           //line:netp:parseuri:endconvert1
	if (uri[strlen(uri)-1] == '/'){                  //line:netp:parseuri:slashcheck
	    strcat(filename, "login.php");               //line:netp:parseuri:appenddefault
	    return 0;
	}
	return 1;
    }
    else {  /* Dynamic content */                        //line:netp:parseuri:isdynamic
	ptr = index(uri, '?');                           //line:netp:parseuri:beginextract
	if (ptr) {
	    strcpy(cgiargs, ptr+1);
	    *ptr = '\0';
	}
	else if(strcasecmp(method,"GET")==0)
	    strcpy(cgiargs, "");                         //line:netp:parseuri:endextract
	strcpy(filename, ".");                           //line:netp:parseuri:beginconvert2
	strcat(filename, uri);                           //line:netp:parseuri:endconvert2
	return 0;
    }
}
/* $end parse_uri */

/*
 * serve_static - copy a file back to the client 
 */
/* $begin serve_static */
void serve_static(int fd, char *filename, int filesize)
{
    int srcfd;
    char *srcp, filetype[MAXLINE], buf[MAXBUF];

    /* Send response headers to client */
    get_filetype(filename, filetype);    //line:netp:servestatic:getfiletype
    sprintf(buf, "HTTP/1.0 200 OK\r\n"); //line:netp:servestatic:beginserve
    if(Rio_writen_EPIPE_free(fd, buf, strlen(buf))==1)
    return;
    sprintf(buf, "Server: FML Server\r\n");
    if(Rio_writen_EPIPE_free(fd, buf, strlen(buf))==1)
    return;
    sprintf(buf, "Content-length: %d\r\n", filesize);
    if(Rio_writen_EPIPE_free(fd, buf, strlen(buf))==1)
    return;
    sprintf(buf, "Content-type: %s\r\n\r\n", filetype);
    if(Rio_writen_EPIPE_free(fd, buf, strlen(buf))==1)
    return;    //line:netp:servestatic:endserve

    /* Send response body to client */
    srcfd = Open(filename, O_RDONLY, 0); //line:netp:servestatic:open
    srcp = Mmap(0, filesize, PROT_READ, MAP_PRIVATE, srcfd, 0); //line:netp:servestatic:mmap
    Close(srcfd);                       //line:netp:servestatic:close
    if(Rio_writen_EPIPE_free(fd, srcp, filesize)==1)
    return;     //line:netp:servestatic:write
    Munmap(srcp, filesize);             //line:netp:servestatic:munmap
}

/*
 * get_filetype - derive file type from file name
 */
void get_filetype(char *filename, char *filetype) 
{
    if (strstr(filename, ".html"))
	strcpy(filetype, "text/html");
    else if (strstr(filename, ".gif"))
	strcpy(filetype, "image/gif");
    else if (strstr(filename, ".png"))
	strcpy(filetype, "image/png");
    else if (strstr(filename, ".jpg"))
	strcpy(filetype, "image/jpeg");
    else
	strcpy(filetype, "text/plain");
}  
/* $end serve_static */

/*
 * serve_dynamic - run a CGI program on behalf of the client
 */
/* $begin serve_dynamic */
void serve_dynamic(int fd, char* method, char *filename, char *cgiargs, int postlen,char* contenttype,char* cookie) 
{
	int fd_fcgi=Open_clientfd("127.0.0.1","9000");//连接php-fpm
	if(Send_to_fastcgi(fd_fcgi,fd,method,filename,cgiargs,postlen,contenttype,cookie)==1){//发送环境变量，POST的body内容到php-fpm
		clienterror(fd, method, "500", "Internal Server Error",
                    "php-fpm broken pipe");
        Close(fd_fcgi);
        return;
	}
	Receive_from_fastcgi(fd,fd_fcgi);//从php-fpm接收内容
	Close(fd_fcgi);
}
/* $end serve_dynamic */

/*
 * clienterror - returns an error message to the client
 */
/* $begin clienterror */
void clienterror(int fd, char *cause, char *errnum, 
		 char *shortmsg, char *longmsg) 
{
    char buf[MAXLINE];

    /* Print the HTTP response headers */
    sprintf(buf, "HTTP/1.0 %s %s\r\n", errnum, shortmsg);
    Rio_writen(fd, buf, strlen(buf));
    sprintf(buf, "Content-type: text/html\r\n\r\n");
    Rio_writen(fd, buf, strlen(buf));

    /* Print the HTTP response body */
    sprintf(buf, "<html><title>FML server Error</title>");
    Rio_writen(fd, buf, strlen(buf));
    sprintf(buf, "<body bgcolor=""ffffff"">\r\n");
    Rio_writen(fd, buf, strlen(buf));
    sprintf(buf, "%s: %s\r\n", errnum, shortmsg);
    Rio_writen(fd, buf, strlen(buf));
    sprintf(buf, "<p>%s: %s\r\n", longmsg, cause);
    Rio_writen(fd, buf, strlen(buf));
    sprintf(buf, "<hr><em>The FML server</em>\r\n");
    Rio_writen(fd, buf, strlen(buf));
}
/* $end clienterror */

void* thread(void* vargp){
	int connfd=*((int*)vargp);
	pthread_detach(pthread_self());
	Free(vargp);
	doit(connfd);
	Close(connfd);
	return NULL;
}
