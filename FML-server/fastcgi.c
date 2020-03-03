#include "fastcgi.h"

int send_fcgi_header(int fd_to_fcsi,int type,int length){//构建header并发送
	fcgi_header t;
	t.version=1;
	t.type=type;
	t.requestIdB1=(fd_to_fcsi>>8);
	t.requestIdB0=(fd_to_fcsi&0xff);
	t.contentLengthB1=(length>>8);
	t.contentLengthB0=(length&0xff);
	t.paddingLength=(((length&7)^7)+1)&7;
	return Rio_writen_EPIPE_free(fd_to_fcsi,&t,sizeof(t));
}

int send_fcgi_beginrequestbody(int fd_to_fcsi,int flag){//构建begin body并发送
	fcgi_beginrequestbody t;
	t.roleB1=0;
	t.roleB0=1;
	t.flags=flag;
	return Rio_writen_EPIPE_free(fd_to_fcsi,&t,sizeof(t));
}

int send_fcgi_params(int fd_to_fcsi,char* name,char* value,int nameLength,int valueLength){//构建params并发送
	char parambuf[16+nameLength+valueLength];
	char* end=parambuf;
	*end++=(char)((nameLength>>24)&0x7f)|0x80;
	*end++=(char)(nameLength>>16)&0xff;
	*end++=(char)(nameLength>>8)&0xff;
	*end++=(char)nameLength&0xff;
	*end++=(char)((valueLength>>24)&0x7f)|0x80;
	*end++=(char)(valueLength>>16)&0xff;
	*end++=(char)(valueLength>>8)&0xff;
	*end++=(char)valueLength&0xff;
	int i;
	for(i=0;i<nameLength;i++)
		*end++=name[i];
	for(i=0;i<valueLength;i++)
		*end++=value[i];
	int paramLength=8+nameLength+valueLength;
	int n=paramLength%8;
	if(n>0)
		return Rio_writen_EPIPE_free(fd_to_fcsi,parambuf,paramLength+8-n);
	else
		return Rio_writen_EPIPE_free(fd_to_fcsi,parambuf,paramLength);
}

int send_fcgi_stdin(char* buf,int fd_to_fcsi,int length){//构建stdin并发送
	int n=length%8;
	if(n>0)
		return Rio_writen_EPIPE_free(fd_to_fcsi,buf,length+8-n);
	else
		return Rio_writen_EPIPE_free(fd_to_fcsi,buf,length);
}

/* fastcgi发送内容的格式：
1.发送一个header，8字节，告知发送的下一项的内容、长度和描述符
2.发送一个内容为beginrequest的包，长度为8
3.重复1
4.发送一个内容为param（环境变量）的包，格式为4字节namelength+4字节valuelength+name+value。3和4可以重复多次，最后发送一个header告知内容为param，长度为0表示param结束发送
5.重复1
6.发送标准输入。结束发送的方式和之前一样
*/
int Send_to_fastcgi(int fd_fcgi,int fd,char* method,char* filename, char* cgiargs, int arglen, char* contenttype, char* cookie){
	//sending begin body
	if(send_fcgi_header(fd_fcgi,1,8)==1){
		printf("send_fcgi_header(type=1) error!");
		return 1;
	}
	if(send_fcgi_beginrequestbody(fd_fcgi,0)==1){
		printf("send_fcgi_beginrequestbody error!");
		return 1;
	}
	//start sending param
	//目前发送的环境变量：REQUEST_METHOD,SCRIPT_FILENAME,COOKIE(假如有),QUERY_STRING(GET时),CONTENT_LENGTH和CONTENT_TYPE(POST时)
	char name[MAXLINE];
	char value[MAXLINE];
	int nameLength;
	int valueLength;
	//REQUEST METHOD
	strcpy(name,"REQUEST_METHOD");
	strcpy(value,method);
	nameLength=strlen(name);
	valueLength=strlen(value);
	if(send_fcgi_header(fd_fcgi,4,8+nameLength+valueLength)==1){
		printf("send_fcgi_header(type=4) error!");
		return 1;
	}
	if(send_fcgi_params(fd_fcgi,name,value,nameLength,valueLength)==1){
		printf("send_fcgi_params error!");
		return 1;
	}
	//SCRIPT FILENAME
	strcpy(name,"SCRIPT_FILENAME");
	char filestr[MAXLINE];
	strcpy(filestr,WORKING_FOLDER);
	strncat(filestr,filename+1,strlen(filename)-1);
	strcpy(value,filestr);
	nameLength=strlen(name);
	valueLength=strlen(value);
	if(send_fcgi_header(fd_fcgi,4,8+nameLength+valueLength)==1){
		printf("send_fcgi_header(type=4) error!");
		return 1;
	}
	if(send_fcgi_params(fd_fcgi,name,value,nameLength,valueLength)==1){
		printf("send_fcgi_params error!");
		return 1;
	}
	if(strlen(cookie)>0){
		//COOKIE
		strcpy(name,"HTTP_COOKIE");
		strcpy(value,cookie);
		nameLength=strlen(name);
		valueLength=strlen(value);
		if(send_fcgi_header(fd_fcgi,4,8+nameLength+valueLength)==1){
			printf("send_fcgi_header(type=4) error!");
			return 1;
		}
		if(send_fcgi_params(fd_fcgi,name,value,nameLength,valueLength)==1){
			printf("send_fcgi_params error!");
			return 1;
		}
	}
	if(strcasecmp(method,"GET")==0){
		//QUERY STRING
		strcpy(name,"QUERY_STRING");
		strcpy(value,cgiargs);
		nameLength=strlen(name);
		valueLength=strlen(value);
		if(send_fcgi_header(fd_fcgi,4,8+nameLength+valueLength)==1){
			printf("send_fcgi_header(type=4) error!");
			return 1;
		}
		if(send_fcgi_params(fd_fcgi,name,value,nameLength,valueLength)==1){
			printf("send_fcgi_params error!");
			return 1;
		}
	}
	if(strcasecmp(method,"POST")==0){
		//CONTENT_LENGTH
		strcpy(name,"CONTENT_LENGTH");
		char len[11];
		len[10]='\0';
		int now=10;
		int tlen=arglen;
		while(tlen>0){
			now--;
			len[now]=tlen%10+'0';
			tlen/=10;
		}
		strcpy(value,len+now);
		nameLength=strlen(name);
		valueLength=strlen(value);
		if(send_fcgi_header(fd_fcgi,4,8+nameLength+valueLength)==1){
			printf("send_fcgi_header(type=4) error!");
			return 1;
		}
		if(send_fcgi_params(fd_fcgi,name,value,nameLength,valueLength)==1){
			printf("send_fcgi_params error!");
			return 1;
		}
		//CONTENT_TYPE
		strcpy(name,"CONTENT_TYPE");
		strcpy(value,contenttype);
		nameLength=strlen(name);
		valueLength=strlen(value);
		if(send_fcgi_header(fd_fcgi,4,8+nameLength+valueLength)==1){
			printf("send_fcgi_header(type=4) error!");
			return 1;
		}
		if(send_fcgi_params(fd_fcgi,name,value,nameLength,valueLength)==1){
			printf("send_fcgi_params error!");
			return 1;
		}
	}
	if(send_fcgi_header(fd_fcgi,4,0)==1){
		printf("send_fcgi_header(type=4) error!");
		return 1;
	}
	//end param
	//start STDIN
	if(strcasecmp(method,"POST")==0){//若是POST，发送请求body内容
	while(arglen>65535){
		if(send_fcgi_header(fd_fcgi,5,65535)==1){
			printf("send_fcgi_header(type=5) error!");
			return 1;
		}
		if(send_fcgi_stdin(cgiargs,fd_fcgi,65535)==1){
			printf("send_fcgi_stdin error!");
			return 1;
		}
		arglen-=65535;
		cgiargs+=65535;
	}
	if(arglen!=0){
		if(send_fcgi_header(fd_fcgi,5,arglen)==1){
			printf("send_fcgi_header(type=5) error!");
			return 1;
		}
		if(send_fcgi_stdin(cgiargs,fd_fcgi,arglen)==1){
			printf("send_fcgi_stdin error!");
			return 1;
		}
	}
	}
	if(send_fcgi_header(fd_fcgi,5,0)==1){
		printf("send_fcgi_header(type=5) error!");
		return 1;
	}
	return 0;
}

void Receive_from_fastcgi(int fd,int fd_fcgi){
	fcgi_header head;
	rio_t rio_fcgi;
	rio_readinitb(&rio_fcgi,fd_fcgi);
	int readsign=0;
	//首先应该收到一个8字节的head
	while(Rio_readnb(&rio_fcgi,&head,sizeof(head))>0){
		//head的type可能是标准输出，标准错误和END，分别判断
		if(head.type==FCGI_STDOUT){
			char buf[MAXLINE];
			int contentLength=(head.contentLengthB1<<8)+head.contentLengthB0;
			int padding=head.paddingLength;
			int tmp;
			int haveread=0;
			if((tmp=Rio_readlineb(&rio_fcgi,buf,contentLength-haveread+1))>0){
				if(readsign==0){
					readsign=1;
					//经过观察，假如Status不是200，会首先显示Status。所以判断第一个STDOUT的第一行，如不是Status则发送200
					if(strstr(buf,"Status: ")){
						char* p=index(buf,':');
						char tstr[MAXLINE];
						strcat(tstr,"HTTP/1.0 ");
						strcat(tstr,p+2);
						if(Rio_writen_EPIPE_free(fd,tstr,strlen(tstr))==1){
							return;
						}
					}
					else if(Rio_writen_EPIPE_free(fd,"HTTP/1.0 200 OK\r\n",strlen("HTTP/1.0 200 OK\r\n"))==1){
						return;
					}
					else if(Rio_writen_EPIPE_free(fd,buf,strlen(buf))==1){
						return;
					}
					//现在只发送Server和Connection:close，及fastcgi传过来的头，一般是content-type和X-Powered-by
					if(Rio_writen_EPIPE_free(fd,"Server: FML server\r\n",strlen("Server: FML server\r\n"))==1){
						return;
					}
					if(Rio_writen_EPIPE_free(fd,"Connection: close\r\n",strlen("Connection: close\r\n"))==1){
						return;
					}
				}
				else if(Rio_writen_EPIPE_free(fd,buf,strlen(buf))==1){
					return;
				}
				haveread+=tmp;
			}
			//只读取共计contentLength个字节
			while((tmp=Rio_readlineb(&rio_fcgi,buf,contentLength-haveread+1))>0){
				haveread+=tmp;
				if(Rio_writen_EPIPE_free(fd,buf,strlen(buf))==1){
					return;
				}
			}
			Rio_readlineb(&rio_fcgi,buf,padding);
		}
		else if(head.type==FCGI_STDERR){
			int contentLength=(head.contentLengthB1<<8)+head.contentLengthB0;
			int padding=head.paddingLength;
			char buf[MAXLINE];
			int haveread=0;
			int tmp;
			while((tmp=Rio_readlineb(&rio_fcgi,buf,contentLength-haveread+1))>0){
				haveread+=tmp;
				printf(buf);
			}
			Rio_readnb(&rio_fcgi,buf,padding);
		}
		//end body只出现在fastcgi返回内容的结尾
		else{
			fcgi_endrequestbody endbody;
			rio_readnb(&rio_fcgi,&endbody,8);
			int appstatus=(endbody.appStatusB3<<24)+(endbody.appStatusB2<<16)+(endbody.appStatusB1<<8)+endbody.appStatusB0;
			char ProtocolStatus='0'+endbody.protocolStatus;
			printf("endbody:%d,%c\n",appstatus,ProtocolStatus);
			break;
		}
	}
}
