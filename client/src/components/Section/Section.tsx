export default function Section({ children, style, className }) {
    return(
        <section 
            className={`py-[6rem] ${className || ''}`}
            style={style}
        >
            {children}
        </section>
    )
}