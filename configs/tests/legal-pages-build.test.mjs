import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');
const sourceRoot = path.join(projectRoot, 'src/plugins/universal-legal-pages/template');
const distRoot = path.join(projectRoot, 'dist/wp-content/plugins/universal-legal-pages');
const frenchTranslationSource = JSON.parse(fs.readFileSync(path.join(projectRoot, 'configs/translations/universal-legal-pages.fr.json'), 'utf8'));
const expectedFrenchMessageCount = Object.keys(frenchTranslationSource.translations).length;
const copiedFiles = [
  'init.php',
  'index.php',
  'readme.txt',
  'templates/single-legal-page.php',
  'assets/css/admin-consent.css',
  'assets/css/legal-pages.css',
  'assets/css/consent-manager.css',
  'assets/js/admin-consent.js',
  'assets/js/consent-manager.js',
  'languages/universal-legal-pages.pot',
  'languages/universal-legal-pages-fr_CA.po',
  'languages/universal-legal-pages-fr_CA.mo',
  'languages/universal-legal-pages-fr_FR.po',
  'languages/universal-legal-pages-fr_FR.mo'
];

const readMoCatalogue = (filename) => {
  const buffer = fs.readFileSync(filename);

  assert.equal(buffer.readUInt32LE(0), 0x950412de, `${path.basename(filename)} does not use the GNU MO little-endian format.`);

  const count = buffer.readUInt32LE(8);
  const originalsOffset = buffer.readUInt32LE(12);
  const translationsOffset = buffer.readUInt32LE(16);
  const catalogue = new Map();
  let previousOriginal = null;

  for(let index = 0; index < count; index += 1){
    const originalLength = buffer.readUInt32LE(originalsOffset + (index * 8));
    const originalOffset = buffer.readUInt32LE(originalsOffset + (index * 8) + 4);
    const translationLength = buffer.readUInt32LE(translationsOffset + (index * 8));
    const translationOffset = buffer.readUInt32LE(translationsOffset + (index * 8) + 4);
    const original = buffer.subarray(originalOffset, originalOffset + originalLength).toString('utf8');
    const translation = buffer.subarray(translationOffset, translationOffset + translationLength).toString('utf8');

    if(previousOriginal !== null){
      assert.ok(
        Buffer.compare(Buffer.from(previousOriginal, 'utf8'), Buffer.from(original, 'utf8')) <= 0,
        `${path.basename(filename)} does not sort original strings in GNU MO byte order.`
      );
    }

    catalogue.set(original, translation);
    previousOriginal = original;
  }

  return catalogue;
};

test('legal pages production artifact is copy-only and synchronized', () => {
  const javascriptDirectory = path.join(distRoot, 'assets/js');
  const javascriptFiles = fs.readdirSync(javascriptDirectory).sort();

  assert.deepEqual(
    javascriptFiles,
    ['admin-consent.js', 'consent-manager.js'],
    'Copy-only plugins must preserve authored JavaScript without generated bundles or sourcemaps.'
  );

  assert.equal(
    fs.existsSync(path.join(javascriptDirectory, 'chunks')),
    false,
    'Copy-only plugins must not retain generated JavaScript chunks.'
  );

  for(const relativeFile of copiedFiles){
    const source = fs.readFileSync(path.join(sourceRoot, relativeFile));
    const generated = fs.readFileSync(path.join(distRoot, relativeFile));

    assert.deepEqual(
      generated,
      source,
      `${relativeFile} differs between authored source and the generated plugin.`
    );
  }

  const sourceMark = fs.readFileSync(path.join(sourceRoot, 'assets/images/reactwp-mark.svg'), 'utf8');
  const generatedMark = fs.readFileSync(path.join(distRoot, 'assets/images/reactwp-mark.svg'), 'utf8');

  assert.ok(generatedMark.length < sourceMark.length, 'The production ReactWP mark should be optimized.');
  assert.match(generatedMark, /<svg[^>]+viewBox="0 0 320 320"[^>]*>/u, 'The optimized mark lost its coordinate system.');
  assert.match(generatedMark, /<svg[^>]+aria-hidden="true"[^>]*>/u, 'The decorative mark must remain hidden from assistive technology.');
  assert.equal((generatedMark.match(/<path\b/gu) || []).length, 1, 'The optimized mark lost its only visible path.');
  assert.doesNotMatch(generatedMark, /<(?:script|foreignObject)\b|\bonload\s*=/iu, 'The generated mark contains executable SVG content.');
});

test('legal pages French catalogues are complete, readable, and synchronized', () => {
  for(const locale of ['fr_CA', 'fr_FR']){
    const sourceCatalogue = readMoCatalogue(path.join(sourceRoot, `languages/universal-legal-pages-${locale}.mo`));
    const generatedCatalogue = readMoCatalogue(path.join(distRoot, `languages/universal-legal-pages-${locale}.mo`));

    assert.equal(
      sourceCatalogue.size,
      expectedFrenchMessageCount + 1,
      `${locale} must include the metadata header and every translated source string.`
    );
    assert.deepEqual(generatedCatalogue, sourceCatalogue, `${locale} translations differ between authored source and the generated plugin.`);
    assert.equal(sourceCatalogue.get('Legal pages'), 'Pages légales');
    assert.equal(sourceCatalogue.get('Consent and integrations'), 'Consentements et intégrations');
    assert.equal(sourceCatalogue.get('Respect the Global Privacy Control signal'), 'Respecter le signal Global Privacy Control');
    assert.equal(sourceCatalogue.get('Consent links language'), 'Langue des liens de consentement');
    assert.equal(sourceCatalogue.get('Custom integrations'), 'Intégrations personnalisées');
    assert.equal(sourceCatalogue.get('Last updated:'), 'Dernière mise à jour :');
    assert.match(sourceCatalogue.get(''), new RegExp(`Language: ${locale}`), `${locale} is missing from its catalogue metadata.`);
  }
});
