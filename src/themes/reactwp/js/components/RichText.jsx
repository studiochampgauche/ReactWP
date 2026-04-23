const RichText = ({ value, className = '' }) => {
    if(!value){
        return null;
    }

    if(typeof value !== 'string'){
        return <div className={className}>{value}</div>;
    }

    return (
        <div
            className={className}
            dangerouslySetInnerHTML={{ __html: value }}
        />
    );
};

export default RichText;