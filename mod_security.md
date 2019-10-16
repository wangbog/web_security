# 安全配置：为Apache配置mod_security

## 安装mod_security

参考 https://github.com/SpiderLabs/ModSecurity

```
yum install mod_security -y
service httpd restart
```



## 配置OWASP Core Rule Set

参考：https://coreruleset.org/installation/

比较完整的参考：https://raw.githubusercontent.com/SpiderLabs/owasp-modsecurity-crs/v3.2/dev/INSTALL

```
yum install git -y

cd /etc/httpd/modsecurity.d/

git clone https://github.com/SpiderLabs/owasp-modsecurity-crs

cp /etc/httpd/modsecurity.d/owasp-modsecurity-crs/crs-setup.conf.example  /etc/httpd/modsecurity.d/owasp-modsecurity-crs/crs-setup.conf

mv /etc/httpd/modsecurity.d/owasp-modsecurity-crs/rules/REQUEST-900-EXCLUSION-RULES-BEFORE-CRS.conf.example /etc/httpd/modsecurity.d/owasp-modsecurity-crs/rules/REQUEST-900-EXCLUSION-RULES-BEFORE-CRS.conf

mv /etc/httpd/modsecurity.d/owasp-modsecurity-crs/rules/RESPONSE-999-EXCLUSION-RULES-AFTER-CRS.conf.example /etc/httpd/modsecurity.d/owasp-modsecurity-crs/rules/RESPONSE-999-EXCLUSION-RULES-AFTER-CRS.conf

vi /etc/httpd/conf/httpd.conf  #添加如下内容：
<IfModule security2_module>
    Include modsecurity.d/owasp-modsecurity-crs/crs-setup.conf
    Include modsecurity.d/owasp-modsecurity-crs/rules/*.conf
</IfModule>

service httpd restart
```



