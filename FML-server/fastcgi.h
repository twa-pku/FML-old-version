#ifndef __FASTCGI_H__
#define __FASTCGI_H__

#include "csapp.h"

#define FCGI_BEGIN_REQUEST       1
#define FCGI_ABORT_REQUEST       2
#define FCGI_END_REQUEST         3
#define FCGI_PARAMS              4
#define FCGI_STDIN               5
#define FCGI_STDOUT              6
#define FCGI_STDERR              7
#define FCGI_DATA                8
#define FCGI_GET_VALUES          9
#define FCGI_GET_VALUES_RESULT  10
#define FCGI_UNKNOWN_TYPE       11
#define FCGI_MAXTYPE (FCGI_UNKNOWN_TYPE)

//fcgi_beginrequestbody roles
#define FCGI_RESPONDER  1
#define FCGI_AUTHORIZER 2
#define FCGI_FILTER     3

//flags
#define FCGI_KEEP_CONN  1

//fcgi_endrequestbody protocolstatus
#define FCGI_REQUEST_COMPLETE 0
#define FCGI_CANT_MPX_CONN    1
#define FCGI_OVERLOADED       2
#define FCGI_UNKNOWN_ROLE     3

//working folder
#define WORKING_FOLDER "/home/FML-server"

typedef struct{
	unsigned char version;
	unsigned char type;
	unsigned char requestIdB1;
	unsigned char requestIdB0;
	unsigned char contentLengthB1;
	unsigned char contentLengthB0;
	unsigned char paddingLength;
	unsigned char reserve;
}fcgi_header;

typedef struct{
	unsigned char roleB1;
	unsigned char roleB0;
	unsigned char flags;
	unsigned char reserved[5];
}fcgi_beginrequestbody;

typedef struct{
	unsigned char appStatusB3;
	unsigned char appStatusB2;
	unsigned char appStatusB1;
	unsigned char appStatusB0;
	unsigned char protocolStatus;
	unsigned char reserved[3];
}fcgi_endrequestbody;

int Send_to_fastcgi(int fd_fcgi,int fd,char* method,char* filename, char* cgiargs, int arglen, char* contenttype, char* cookie);
void Receive_from_fastcgi(int fd,int fd_fcgi);
int send_fcgi_header(int fd_to_fcsi,int type,int length);
int send_fcgi_beginrequestbody(int fd_to_fcsi,int flag);
int send_fcgi_params(int fd_to_fcsi,char* name,char* value,int nameLength,int valueLength);
int send_fcgi_stdin(char* buf,int fd_to_fcsi,int length);
#endif
