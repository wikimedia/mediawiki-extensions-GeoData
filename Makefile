MW_INSTALL_PATH ?= ../..

default:

	${MW_INSTALL_PATH}/vendor/bin/phpunit --group=GeoData
