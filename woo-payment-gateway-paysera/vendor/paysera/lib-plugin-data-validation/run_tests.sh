#!/bin/bash

docker build -t lib_plugin_data_validatior_tests -f $PWD/Dockerfile $PWD
docker run -it -v $PWD:/var/www -w /var/www lib_plugin_data_validatior_tests composer i
docker run -it -v $PWD:/var/www -w /var/www lib_plugin_data_validatior_tests composer run phpunit
