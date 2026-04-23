import '../../scss/templates/reactwp.scss';
import Button from '../components/Button';

const Default = ({ route, site }) => {
	const headline = route.data.hero_title || 'Installation ready';
	const intro = route.data.hero_intro || 'Your ReactWP starter is installed and ready. You can now edit the site in WordPress and start customizing the frontend.';

	return(
		<div className="status-screen status-screen--ready">
			<section className="status-screen__section">
				<div className="container status-screen__shell">
					<div className="status-card">
						<span className="status-card__eyebrow">ReactWP v3</span>
						<h1>{headline}</h1>
						<p className="status-card__lead">{intro}</p>

						<div className="status-card__actions">
							<Button href={'/wp-admin'} variant="primary" data-router="false">Open WordPress Admin</Button>
						</div>
					</div>
				</div>
			</section>
		</div>
	);
};

export default Default;