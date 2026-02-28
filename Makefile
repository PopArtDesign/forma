js/forma.min.js: js/forma.js
	npx esbuild \
		--target=es2017 \
		--bundle \
		--minify \
		--outfile=js/forma.min.js \
		js/forma.js

forma.zip:
	git archive -o forma.zip HEAD

.PHONY: build
build: forma.zip

.PHONY: test
test:
	curl -X POST -k \
		-H 'Accept-Language: en,en-US;q=0.9' \
		-d 'name=John Doe&phone=+71234567890' \
		-d 'forma_client_info={"url":"test","title":"test"}' \
		-d 'forma_imnotarobot=imnotarobot!' \
		https://127.0.0.1:8000/test/handler.php
