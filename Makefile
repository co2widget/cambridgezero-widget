VERSION=$(shell git rev-parse HEAD)

build-image:
	docker buildx build --platform linux/amd64 . -t quay.io/hughsimpson/co2widget:$(VERSION) --load

deploy: build-image
	docker tag quay.io/hughsimpson/co2widget:$(VERSION) quay.io/hughsimpson/co2widget:latest
	docker push quay.io/hughsimpson/co2widget:$(VERSION)
	docker push quay.io/hughsimpson/co2widget:latest

# runs on port 82
run-locally: build-image
	@(docker rm -f co2widget || echo no running image) && docker run -d -p82:80 --name=co2widget --platform=linux/amd64 quay.io/hughsimpson/co2widget:$(VERSION)
