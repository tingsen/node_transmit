# node_transmit
文献传输,单篇 多篇文献PDF下载

自己单独开发的一个drupal 文献传递模块
其中包含功能点
1.文献传递,需要加一些限制条件1.是否登录2.申请列表3.生成链接4.发送邮件
2.服务器上不存文件，需要给客户端数据流，下载完后删除文件
3.单篇内网ip下载已经完成，多篇内网ip下载接口，返回的是下载链接
4.外网审核流程，后台列表需要显示用户申请传递文献的名字，验证码验证。需要操作审核的动作
composer require gregwar/captcha
5.内容结构的更改。还有附件导入的时候储存方式。
6.txt转化为PDF导出，后台需要对列表筛选，统一审核的的按钮。个人中心显示申请列表，发送邮件告知用户。
composer require tecnickcom/tcpdf
乱码问题需要配置中文字体
composer require 'drupal/smtp:^1.0'
