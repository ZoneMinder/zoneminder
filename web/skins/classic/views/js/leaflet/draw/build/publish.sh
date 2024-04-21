#!/bin/bash

npm update

VERSION=$(node --eval "console.log(require('./package.json').version);")

npm test || exit 1

git checkout -b build

jake build[,,true]
jake docs

git add \
    dist/leaflet.draw.js \
    dist/leaflet.draw-src.js \
    dist/leaflet.draw-src.map \
    dist/leaflet.draw.css \
    dist/leaflet.draw-src.css \
    docs/* \
    -f

git commit -m "v$VERSION"

git tag v$VERSION -f
git push --tags -f

npm publish

git checkout master
git branch -D build
