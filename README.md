# FML kit

FML(Fantasy Manager League)是[北大未名BBS Sports_Game](https://bbs.pku.edu.cn/v2/thread.php?bid=519&mode=topic)版网友进行的版面足球游戏。FML kit是对于该游戏数据处理和展示问题的一个解决方案，包括FML server和FML site两部分，分别是服务器和网页。项目网址为[http://fmlpku.com:45807](http://fmlpku.com:45807)。  
目前页面如下（还没有写css:-()。所有的功能均已经可以使用。
![png](https://github.com/twa-pku/FML/blob/master/FML-site/site.PNG)  

## Contents

- [FML server](#FML-server)
- [FML site](#FML-site)
- [更新记录](#更新记录)

## FML server
一个以CSAPP书中tiny服务器为原型扩展而来的服务器，可支持POST方法，支持多线程运行，及处理php文件。  

csapp.c和csapp.h基本是csapp的源码，其中加了Rio_writen_EPIPE_free函数使服务器在面对EPIPE和ECONNRESET错误时不结束运行。  
FML-server.c由tiny.c改写，使其支持POST，支持多线程运行，对请求头进行更多的处理，能调用fastcgi.h中定义的函数处理php文件，并能处理SIGPIPE信号。  
fastcgi.c和fastcgi.h使得服务器能将运行php所需要的信息用fastcgi的方式传递给php-fpm，并读取php-fpm传回的内容。  
此服务器只能处理php形式的动态内容。  

### 使用方法
- 安装php和mysql，安装时enable php-fpm  
- make  
- 把文件cp到该文件夹，更改fastcgi.h中的WORKING_FOLDER  

### todo list  
- 支持keep-alive  
- 支持https  
- 优化代码，进一步增强性能与健壮性  

## FML site
一个用来处理FML游戏流程，并展示数据的网站。  

### 目前已经实现的功能：  
#### 处理每轮FML比赛  
- 由玩家手动添加进球，进球可由管理人员撤回  
- 显示实时比分，实时积分榜  
- 结束比赛时自动更新积分榜、射手榜、球队其它信息并保存比赛相关文件供查阅  
#### 处理转会
- 可由管理人员添加玩家之间的转会、自由签、解约，系统自动判定每次操作是否有效  
#### 丰富的查询功能  
- 所有人可通过查询球员名/球员编号/现实中球队名/FML中球队名查询球员
#### 查看历史记录
- 可快速查看历史直播帖、积分榜、射手榜  
#### 方便地查看游戏相关文件、比赛状态、最近更新记录及游戏相关网站  
#### 简单的日志系统，供后台查阅操作  
#### 一个极为简单的账号系统  

### 目前尚未实现的功能：  
- 处理暗标  
- 让玩家在网站上提交转会、自由签和解约  
- 对FMC等其它赛事的支持  
- 爬取footballsqurds网页功能  

### todo list
- 为页面写css，美观页面  
- 让页面适合手机显示  
- 优化账号系统  
- 使管理人员能爬取footballsqurds以更新球员大名单  
- 支持FMC等杯赛类赛事  
- 如有必要，实现暗标处理等功能  

## 更新记录

### 2020-03-06
- 更改添加进球方式
- 限制查找球员最大显示数目
- 微调了一些显示事项
- 使手机能通过微信里的链接打开网页
- 使服务器能处理ECONNRESET错误
