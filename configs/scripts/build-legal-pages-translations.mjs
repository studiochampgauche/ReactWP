import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const configsRoot = path.resolve(__dirname, '..');
const projectRoot = path.resolve(configsRoot, '..');
const pluginRoot = path.join(projectRoot, 'src/plugins/universal-legal-pages/template');
const languagesRoot = path.join(pluginRoot, 'languages');
const cataloguePath = path.join(configsRoot, 'translations/universal-legal-pages.fr.json');
const catalogue = JSON.parse(fs.readFileSync(cataloguePath, 'utf8'));
const translations = new Map(Object.entries(catalogue.translations));
const translationCallPattern = /(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x)\(\s*'((?:\\.|[^'\\])*)'\s*,\s*'universal-legal-pages'/gu;
const decodePhpSingleQuoted = (value) => value.replace(/\\([\\'])/gu, '$1');

const phpFiles = [];

const collectPhpFiles = (directory) => {
  for(const entry of fs.readdirSync(directory, { withFileTypes: true })){
    const absolutePath = path.join(directory, entry.name);

    if(entry.isDirectory()){
      collectPhpFiles(absolutePath);
    }else if(entry.isFile() && entry.name.endsWith('.php')){
      phpFiles.push(absolutePath);
    }
  }
};

collectPhpFiles(pluginRoot);

const sourceReferences = new Map();

for(const filename of phpFiles){
  const source = fs.readFileSync(filename, 'utf8');
  let match;

  while((match = translationCallPattern.exec(source))){
    const message = decodePhpSingleQuoted(match[1]);
    const line = source.slice(0, match.index).split('\n').length;
    const relativePath = path.relative(pluginRoot, filename).replaceAll(path.sep, '/');
    const references = sourceReferences.get(message) || [];

    references.push(`${relativePath}:${line}`);
    sourceReferences.set(message, references);
  }
}

const sourceMessages = [...sourceReferences.keys()].sort((first, second) => first.localeCompare(second, 'en'));
const missingTranslations = sourceMessages.filter((message) => !translations.has(message));
const obsoleteTranslations = [...translations.keys()].filter((message) => !sourceReferences.has(message));

if(missingTranslations.length || obsoleteTranslations.length){
  const details = [
    missingTranslations.length ? `Missing French translations:\n- ${missingTranslations.join('\n- ')}` : '',
    obsoleteTranslations.length ? `Obsolete French translations:\n- ${obsoleteTranslations.join('\n- ')}` : ''
  ].filter(Boolean).join('\n\n');

  throw new Error(details);
}

const quotePo = (value) => JSON.stringify(value);
const commonHeaders = [
  `Project-Id-Version: ${catalogue.project} ${catalogue.version}`,
  'Report-Msgid-Bugs-To:',
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'Content-Transfer-Encoding: 8bit',
  'X-Domain: universal-legal-pages'
];

const poHeader = (language) => [
  ...commonHeaders,
  `Language: ${language}`,
  'Plural-Forms: nplurals=2; plural=(n > 1);'
].join('\n') + '\n';

const potHeader = [...commonHeaders, 'Language:'].join('\n') + '\n';

const formatHeader = (header) => {
  const lines = header.split('\n').filter(Boolean);

  return [
    'msgid ""',
    'msgstr ""',
    ...lines.map((line) => quotePo(`${line}\n`))
  ].join('\n');
};

const formatEntries = (language = '') => sourceMessages.map((message) => {
  const references = [...new Set(sourceReferences.get(message))].join(' ');
  const translated = language ? translations.get(message) : '';

  return [
    ...(/%(?:\d+\$)?[sd]/u.test(message) ? ['#, php-format'] : []),
    `#: ${references}`,
    `msgid ${quotePo(message)}`,
    `msgstr ${quotePo(translated)}`
  ].join('\n');
}).join('\n\n');

const buildMo = (language) => {
  const messages = new Map([['', poHeader(language)], ...sourceMessages.map((message) => [message, translations.get(message)])]);
  const sortedMessages = [...messages.entries()].sort(([first], [second]) => {
    return Buffer.compare(Buffer.from(first, 'utf8'), Buffer.from(second, 'utf8'));
  });
  const originals = sortedMessages.map(([message]) => Buffer.from(message, 'utf8'));
  const localized = sortedMessages.map(([, translation]) => Buffer.from(translation, 'utf8'));
  const count = sortedMessages.length;
  const originalsTableOffset = 28;
  const translationsTableOffset = originalsTableOffset + (count * 8);
  const originalsDataOffset = translationsTableOffset + (count * 8);
  const originalsSize = originals.reduce((total, value) => total + value.length + 1, 0);
  const translationsDataOffset = originalsDataOffset + originalsSize;
  const translationsSize = localized.reduce((total, value) => total + value.length + 1, 0);
  const output = Buffer.alloc(translationsDataOffset + translationsSize);

  output.writeUInt32LE(0x950412de, 0);
  output.writeUInt32LE(0, 4);
  output.writeUInt32LE(count, 8);
  output.writeUInt32LE(originalsTableOffset, 12);
  output.writeUInt32LE(translationsTableOffset, 16);
  output.writeUInt32LE(0, 20);
  output.writeUInt32LE(0, 24);

  let originalOffset = originalsDataOffset;
  let translationOffset = translationsDataOffset;

  sortedMessages.forEach((entry, index) => {
    const original = originals[index];
    const translation = localized[index];

    output.writeUInt32LE(original.length, originalsTableOffset + (index * 8));
    output.writeUInt32LE(originalOffset, originalsTableOffset + (index * 8) + 4);
    original.copy(output, originalOffset);
    originalOffset += original.length + 1;

    output.writeUInt32LE(translation.length, translationsTableOffset + (index * 8));
    output.writeUInt32LE(translationOffset, translationsTableOffset + (index * 8) + 4);
    translation.copy(output, translationOffset);
    translationOffset += translation.length + 1;
  });

  return output;
};

fs.mkdirSync(languagesRoot, { recursive: true });
fs.writeFileSync(
  path.join(languagesRoot, 'universal-legal-pages.pot'),
  `${formatHeader(potHeader)}\n\n${formatEntries()}\n`,
  'utf8'
);

for(const language of ['fr_CA', 'fr_FR']){
  fs.writeFileSync(
    path.join(languagesRoot, `universal-legal-pages-${language}.po`),
    `${formatHeader(poHeader(language))}\n\n${formatEntries(language)}\n`,
    'utf8'
  );
  fs.writeFileSync(
    path.join(languagesRoot, `universal-legal-pages-${language}.mo`),
    buildMo(language)
  );
}

console.log(`Built ${sourceMessages.length} Universal Legal Pages translations for fr_CA and fr_FR.`);
