js/forma.min.js: js/forma.js
	npx esbuild \
		--target=es2017 \
		--bundle \
		--minify \
		--outfile=js/forma.min.js \
		js/forma.js
