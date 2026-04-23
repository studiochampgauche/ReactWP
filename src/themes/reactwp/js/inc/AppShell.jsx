import Header from '../components/Header';
import Footer from '../components/Footer';

const AppShell = ({
    children,
    showHeader = false,
    showFooter = false,
    headerProps = {},
    footerProps = {}
}) => {
    return (
        <div className="app-shell">
            <Header show={showHeader} {...headerProps} />

            <div className="app-shell__body">
                {children}
            </div>

            <Footer show={showFooter} {...footerProps} />
        </div>
    );
};

export default AppShell;
