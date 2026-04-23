import '../../scss/templates/reactwp.scss';
import Button from '../components/Button';

const NotFound = ({ route }) => {
	return(
		<div className="status-screen status-screen--not-found">
			<section className="status-screen__section">
				<div className="container status-screen__shell">
					<div className="status-card status-card--not-found">
						<span className="status-card__eyebrow">404</span>
						<h1>Page not found</h1>
						<p className="status-card__lead">This URL does not match a published route yet.</p>
						<p className="status-card__meta"><code>{route.path}</code></p>
						<div className="status-card__actions">
							<Button to="/">Back to home</Button>
							<Button variant="ghost" onClick={() => window.history.back()}>Go back</Button>
						</div>
					</div>
				</div>
			</section>
		</div>
	);
}

export default NotFound;
