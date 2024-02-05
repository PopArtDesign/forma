js/forma.min.js: js/forma.js
	npx esbuild \
		--target=es2017 \
		--minify \
		--outfile=js/forma.min.js \
		js/forma.js
