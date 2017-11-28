#!/bin/sh
cat /etc/nginx/conf.d/default.conf.template
echo "Creating new config..."
if [ -z "$RESOLVER" ]; then
echo "Variable \$RESOLVER is required.";
exit 1;
fi

if [ -z "$PHPFPM" ]; then
echo "Variable \$PHPFPM is required.";
exit 1;
fi

if [ -z "$SOURCE_PATH" ]; then
echo "Variable \$SOURCE_PATH is required.";
exit 1;

fi

envsubst "\$PHPFPM \$RESOLVER \$SOURCE_PATH" < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
retval=$?
if [ $retval -ne 0 ]; then
  echo "Envsubst failed.";
  exit $retval;
fi

cat /etc/nginx/conf.d/default.conf
exec /usr/sbin/nginx -g "daemon off;"