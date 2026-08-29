import '../../scss/templates/reactwp-not-found.scss';
import AppLink from '../components/AppLink';
import Button from '../components/Button';

const themeImagePath = '/wp-content/themes/reactwp/assets/images';

const Wordmark = () => (
	<AppLink className="reactwp-wordmark" to="/" aria-label="ReactWP home">
		<img src={`${themeImagePath}/reactwp-wordmark.svg`} alt="" width="1691" height="311" />
	</AppLink>
);

const NotFound = ({ route }) => {
	const requestedPath = route?.path || '/';

	const goBack = () => {
		if(typeof window === 'undefined'){
			return;
		}

		if(window.history.length > 1){
			window.history.back();
			return;
		}

		window.location.assign('/');
	};

	return(
		<div className="reactwp-not-found">
			<header className="reactwp-not-found__header">
				<div className="reactwp-shell reactwp-not-found__header-inner">
					<Wordmark />
					<a href="https://reactwp.com/docs/intro/">Documentation</a>
				</div>
			</header>

			<div className="reactwp-not-found__main">
				<section className="reactwp-shell reactwp-not-found__layout" aria-labelledby="reactwp-not-found-title">
					<div className="reactwp-not-found__content">
						<p className="reactwp-not-found__error">404</p>
						<h1 id="reactwp-not-found-title"><span>No page</span> here.</h1>
						<p className="reactwp-not-found__lead">ReactWP couldn’t match this address to a public route.</p>

						<p className="reactwp-not-found__path">
							<span>Requested path</span>
							<code dir="auto"><bdi>{requestedPath}</bdi></code>
						</p>

						<nav className="reactwp-not-found__actions" aria-label="Page recovery">
							<Button to="/" variant="primary">Back home</Button>
							<a className="reactwp-not-found__documentation" href="https://reactwp.com/docs/intro/">Read the docs</a>
							<Button type="button" variant="text" onClick={goBack}>Go back</Button>
						</nav>
					</div>

					<div className="reactwp-not-found__signal" aria-hidden="true">
						<img src={`${themeImagePath}/reactwp-mark.svg`} alt="" width="320" height="320" />
						<strong>404</strong>
						<span>Built by Studio Champ Gauche</span>
					</div>
				</section>
			</div>
		</div>
	);
};

export default NotFound;
