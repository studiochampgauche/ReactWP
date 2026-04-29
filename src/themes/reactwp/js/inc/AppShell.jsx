import Header from '../components/Header';
import Footer from '../components/Footer';

const AppShell = ({
    children,
    showHeader = false,
    showFooter = false,
    headerKey = null,
    footerKey = null,
    headerProps = {},
    footerProps = {}
}) => {
    const resolvedHeaderKey = headerKey !== null ? `header-${headerKey}` : null;
    const resolvedFooterKey = footerKey !== null ? `footer-${footerKey}` : null;

    return (
        <div className="app-shell">
            <Header key={resolvedHeaderKey} show={showHeader} {...headerProps} />

            <div className="app-shell__body">
                {children}
            </div>

            <Footer key={resolvedFooterKey} show={showFooter} {...footerProps} />
        </div>
    );
};

export default AppShell;
