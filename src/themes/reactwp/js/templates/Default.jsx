import '../../scss/templates/reactwp.scss';
import AppLink from '../components/AppLink';
import Button from '../components/Button';
import RichText from '../components/RichText';

const starterLeadTags = [
	'a', 'abbr', 'b', 'br', 'cite', 'code', 'em', 'i', 'p', 'q', 's',
	'small', 'span', 'strong', 'sub', 'sup', 'u'
];

const frontendPaths = [
	{
		name: 'Stay inside WordPress.',
		description: 'WordPress resolves the request, ReactWP loads the registered template, and the browser runtime handles client navigation.',
		link: 'https://reactwp.com/docs/templates-and-pages/',
		linkLabel: 'Build an integrated template'
	},
	{
		name: 'Bring your own frontend.',
		description: 'Use ReactWP’s versioned public API while your application owns rendering and navigation.',
		link: 'https://reactwp.com/docs/headless-quick-start/',
		linkLabel: 'Go headless'
	}
];

const routeFlow = [
	{
		name: 'Resolve',
		description: 'WordPress matches the public request.'
	},
	{
		name: 'Normalize',
		description: 'ReactWP prepares a consistent route payload.'
	},
	{
		name: 'Select',
		description: 'The registry loads the template and its render mode.'
	},
	{
		name: 'Deliver',
		description: 'The configured client, static, or server response reaches the browser.'
	}
];

const renderModes = [
	{
		name: 'Client',
		identifier: 'client',
		description: 'React renders in the browser. No Node.js production service is required.'
	},
	{
		name: 'Static',
		identifier: 'static',
		description: 'HTML is generated ahead of time, then hydrated in the browser.'
	},
	{
		name: 'Server',
		identifier: 'server',
		description: 'HTML is rendered for each request, then hydrated. The production render service is required.'
	}
];

const templateExample = `registerTemplate('About', {
    loader: () => import('../../templates/About'),
    render: 'static'
});`;

const projectDirectories = [
	{
		path: 'src/',
		description: 'Author WordPress, PHP, React, SCSS, plugins, and media here.'
	},
	{
		path: 'configs/',
		description: 'Run project commands and maintain the build configuration here.'
	},
	{
		path: 'dist/',
		description: 'Serve the generated WordPress installation as the document root. Do not edit it by hand.'
	}
];

const themeImagePath = '/wp-content/themes/reactwp/assets/images';

const Wordmark = () => (
	<AppLink className="reactwp-wordmark" to="/" aria-label="ReactWP home">
		<img src={`${themeImagePath}/reactwp-wordmark.svg`} alt="" width="1691" height="311" />
	</AppLink>
);

const BrandField = () => (
	<div className="reactwp-demo__brand-field" aria-hidden="true">
		<img src={`${themeImagePath}/reactwp-mark.svg`} alt="" width="320" height="320" />
	</div>
);

const Default = ({ route }) => {
	const data = route?.data || {};
	const headline = data.hero_title || null;
	const intro = data.hero_intro || 'ReactWP is configured. Keep WordPress in charge, build in the integrated React theme, or connect an external frontend.';

	return(
		<div id="top" className="reactwp-demo">
			<header className="reactwp-demo__header">
				<div className="reactwp-shell reactwp-demo__header-inner">
					<Wordmark />

					<nav className="reactwp-demo__nav" aria-label="ReactWP sections">
						<AppLink to="#architecture" data-router="false">Architecture</AppLink>
						<AppLink to="#rendering" data-router="false">Rendering</AppLink>
						<AppLink to="#project-map" data-router="false">Project</AppLink>
						<a href="https://reactwp.com/docs/intro/">Docs</a>
					</nav>

					<Button className="reactwp-demo__admin-link" href="/wp-admin/" variant="primary" data-router="false">Open admin</Button>
				</div>
			</header>

			<div className="reactwp-demo__main">
				<section className="reactwp-demo__hero" aria-labelledby="reactwp-hero-title">
					<div className="reactwp-shell reactwp-demo__hero-grid">
						<div className="reactwp-demo__hero-copy">
							<p className="reactwp-demo__status"><span aria-hidden="true"></span>Setup complete</p>
							<h1 id="reactwp-hero-title">{headline || <>It <span className="reactwp-demo__hero-invert">works.</span> Now make it yours.</>}</h1>
							<RichText value={intro} className="reactwp-demo__intro" allowedTags={starterLeadTags} />
							<div className="reactwp-demo__hero-actions">
								<Button href="/wp-admin/" variant="primary" data-router="false">Open WordPress admin</Button>
								<a className="reactwp-demo__text-link" href="https://reactwp.com/docs/getting-started/">Read the setup guide</a>
							</div>
						</div>

						<BrandField />
					</div>
				</section>

				<section id="architecture" className="reactwp-demo__architecture" aria-labelledby="reactwp-architecture-title">
					<div className="reactwp-shell">
						<header className="reactwp-demo__section-heading">
							<h2 id="reactwp-architecture-title">One backend. Pick a frontend.</h2>
							<p>WordPress keeps content, users, plugins, previews, and permalinks. Build inside ReactWP or bring your own frontend.</p>
						</header>

						<div className="reactwp-demo__pathways">
							{frontendPaths.map((path) => (
								<article key={path.name} className="reactwp-demo__pathway">
									<h3>{path.name}</h3>
									<p>{path.description}</p>
									<a className="reactwp-demo__text-link" href={path.link}>{path.linkLabel}</a>
								</article>
							))}
						</div>

						<div className="reactwp-demo__route-layout">
							<div className="reactwp-demo__route-intro">
								<h3>Request in. React out.</h3>
								<p>WordPress resolves the route, ReactWP normalizes it, the registry selects a template, and the configured response reaches the browser.</p>
							</div>

							<ol className="reactwp-demo__route-steps">
								{routeFlow.map((step) => (
									<li key={step.name}>
										<h4>{step.name}</h4>
										<p>{step.description}</p>
									</li>
								))}
							</ol>
						</div>
					</div>
				</section>

				<section id="rendering" className="reactwp-demo__rendering" aria-labelledby="reactwp-rendering-title">
					<div className="reactwp-shell">
						<header className="reactwp-demo__section-heading">
							<h2 id="reactwp-rendering-title">Client. Static. Server. Your call.</h2>
							<p>Integrated templates share one component contract. Choose the first render each route needs.</p>
						</header>

						<div className="reactwp-demo__render-layout">
							<ul className="reactwp-demo__render-modes">
								{renderModes.map((mode) => (
									<li key={mode.identifier}>
										<code>{mode.identifier}</code>
										<div>
											<h3>{mode.name}</h3>
											<p>{mode.description}</p>
										</div>
									</li>
								))}
							</ul>

							<figure className="reactwp-demo__code-artifact">
								<figcaption>A template registration declares its loader and initial render.</figcaption>
								<pre><code>{templateExample}</code></pre>
								<a className="reactwp-demo__text-link" href="https://reactwp.com/docs/hybrid-rendering/">Compare rendering modes</a>
							</figure>
						</div>
					</div>
				</section>

				<section id="project-map" className="reactwp-demo__project-map" aria-labelledby="reactwp-project-map-title">
					<div className="reactwp-shell">
						<header className="reactwp-demo__section-heading">
							<h2 id="reactwp-project-map-title">Source stays source.</h2>
							<p>Edit <code>src/</code>. Run project commands in <code>configs/</code>. Serve <code>dist/</code>. Never hand-edit generated output.</p>
						</header>

						<div className="reactwp-demo__project-layout">
							<dl className="reactwp-demo__directories">
								{projectDirectories.map((directory) => (
									<div key={directory.path}>
										<dt><code>{directory.path}</code></dt>
										<dd>{directory.description}</dd>
									</div>
								))}
							</dl>

							<div className="reactwp-demo__commands" aria-label="ReactWP build commands">
								<figure>
									<figcaption>Development</figcaption>
									<pre><code>{`cd configs\nnpm run watch`}</code></pre>
								</figure>
								<figure>
									<figcaption>Production</figcaption>
									<pre><code>{`cd configs\nnpm run prod`}</code></pre>
								</figure>
							</div>
						</div>
					</div>
				</section>

				<section className="reactwp-demo__close" aria-labelledby="reactwp-close-title">
					<div className="reactwp-shell reactwp-demo__close-grid">
						<div>
							<h2 id="reactwp-close-title">Starter over. Build the real thing.</h2>
							<p>Replace <code>Default.jsx</code>, register the next template, and choose its render mode.</p>
						</div>

						<div className="reactwp-demo__close-actions">
							<Button href="https://reactwp.com/docs/templates-and-pages/" variant="primary">Read the template guide</Button>
							<AppLink className="reactwp-demo__text-link" to="/wp-admin/" data-router="false">Open WordPress admin</AppLink>
						</div>
					</div>
				</section>
			</div>

			<footer className="reactwp-demo__footer">
				<div className="reactwp-shell reactwp-demo__footer-inner">
					<Wordmark />
					<p>ReactWP 3 · GPL-2.0-or-later</p>
					<nav aria-label="ReactWP resources">
						<a href="https://reactwp.com/docs/intro/">Documentation</a>
						<a href="https://github.com/studiochampgauche/ReactWP">GitHub</a>
						<AppLink to="#top" data-router="false">Back to top</AppLink>
					</nav>
				</div>
			</footer>
		</div>
	);
};

export default Default;
