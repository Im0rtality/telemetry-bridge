default: clean protoc composer run

setup:
	brew install bufbuild/buf/buf

protoc: clean-protoc
	buf generate

clean-protoc:
	rm -rf src/generated

composer:
	composer install
	composer dump-autoload

clean-composer:
	rm -rf vendor/

clean: clean-protoc clean-composer

run:
	php bin/server.php

docker-build: clean protoc composer
	docker build -t metrics_pb -f .docker/Dockerfile .

docker-run:
	docker run -it --rm -e metrics_pb
