CC = gcc
CFLAGS = -O2 -Wall -I .

# This flag includes the Pthreads library on a Linux box.
# Others systems will probably require something different.
LIB = -lpthread

all: FMLserver

FMLserver: FMLserver.c csapp.o fastcgi.o
	$(CC) $(CFLAGS) -o FMLserver FMLserver.c csapp.o fastcgi.o $(LIB)

csapp.o: csapp.c
	$(CC) $(CFLAGS) -c csapp.c

fastcgi.o: fastcgi.c
	$(CC) $(CFLAGS) -c fastcgi.c

clean:
	rm -f *.o tiny *~
