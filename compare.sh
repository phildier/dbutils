
server1=auditor:'jH6#bJ2MD2gqM4'@production-auditor.cjyftyhe2kug.us-east-1.rds.amazonaws.com
server2=auditor:89VzD8Wtcc3NtY@stage-auditor.cjyftyhe2kug.us-east-1.rds.amazonaws.com

mysqldbcompare \
	--server1=$server1 \
	--server2=$server2 \
	--skip-data-check \
	--skip-object-compare \
	--skip-row-count \
	--run-all-tests \
	--changes-for=server1 \
	--difftype=sql \
	auditor:auditor
