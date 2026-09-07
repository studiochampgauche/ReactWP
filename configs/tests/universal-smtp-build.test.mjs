import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');
const sourceRoot = path.join(projectRoot, 'src/plugins/universal-smtp/template');
const distRoot = path.join(projectRoot, 'dist/wp-content/plugins/universal-smtp');
const frenchTranslationSource = JSON.parse(fs.readFileSync(path.join(projectRoot, 'configs/translations/universal-smtp.fr.json'), 'utf8'));
const expectedFrenchMessageCount = Object.keys(frenchTranslationSource.translations).length;
const copiedFiles = [
  'init.php',
  'index.php',
  'readme.txt',
  'assets/css/admin-smtp.css',
  'assets/js/admin-smtp.js',
  'languages/universal-smtp.pot',
  'languages/universal-smtp-fr_CA.po',
  'languages/universal-smtp-fr_CA.mo',
  'languages/universal-smtp-fr_FR.po',
  'languages/universal-smtp-fr_FR.mo'
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

test('Universal SMTP production artifact is copy-only and synchronized', () => {
  const javascriptDirectory = path.join(distRoot, 'assets/js');
  const javascriptFiles = fs.readdirSync(javascriptDirectory).sort();

  assert.deepEqual(
    javascriptFiles,
    ['admin-smtp.js'],
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
});

test('Universal SMTP French catalogues are complete, readable, and synchronized', () => {
  for(const locale of ['fr_CA', 'fr_FR']){
    const sourceCatalogue = readMoCatalogue(path.join(sourceRoot, `languages/universal-smtp-${locale}.mo`));
    const generatedCatalogue = readMoCatalogue(path.join(distRoot, `languages/universal-smtp-${locale}.mo`));

    assert.equal(
      sourceCatalogue.size,
      expectedFrenchMessageCount + 1,
      `${locale} must include the metadata header and every translated source string.`
    );
    assert.deepEqual(generatedCatalogue, sourceCatalogue, `${locale} translations differ between authored source and the generated plugin.`);
    assert.equal(sourceCatalogue.get('SMTP host'), 'Hôte SMTP');
    assert.equal(sourceCatalogue.get('Send test email'), 'Envoyer le courriel de test');
    assert.equal(sourceCatalogue.get('Settings saved.'), 'Réglages enregistrés.');
    assert.match(sourceCatalogue.get(''), new RegExp(`Language: ${locale}`), `${locale} is missing from its catalogue metadata.`);
  }
});
