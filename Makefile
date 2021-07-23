build-image:
	docker build . -t quay.io/hughsimpson/co2widget:latest

deploy: build-image
	docker push quay.io/hughsimpson/co2widget:latest

# runs on port 82
run-locally: build-image
	docker run -d -p82:80 quay.io/hughsimpson/co2widget:latest
