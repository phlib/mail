#!/bin/bash
/usr/local/bin/docker run -it \
    -v `pwd`:/var/www \
    -w /var/www \
    --rm=true \
    phlib_application /bin/bash 
