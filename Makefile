MW_INSTALL_PATH ?= ../..

default:

	cd ${MW_INSTALL_PATH}/tests/phpunit && php phpunit.php --group=GeoData
