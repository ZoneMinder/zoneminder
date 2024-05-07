
var packageDef = require('../package.json');

function buildDocs() {

	console.log('Building Leaflet Draw documentation with Leafdoc');

	var LeafDoc = require('leafdoc');
	var doc = new LeafDoc({
		templateDir: 'build/leafdoc-templates',
		showInheritancesWhenEmpty: true,
		leadingCharacter: '@'
	});

	//doc.setLeadingChar('@');
	doc.addFile('build/docs-index.leafdoc', false);
	doc.addDir('src');
	doc.addFile('build/docs-misc.leafdoc', false);


	var out = doc.outputStr();

	var fs = require('fs');

	fs.writeFileSync('docs/leaflet-draw-latest.html', out);
	fs.writeFileSync('docs/leaflet-draw-' + packageDef.version + '.html', out);
}

module.exports = buildDocs;
