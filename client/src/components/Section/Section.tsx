import type { ComponentPropsWithoutRef } from 'react';

type SectionProps = ComponentPropsWithoutRef<'section'>;

export default function Section({ children, style, className, ...props }: SectionProps) {
    return(
        <section 
            {...props}
            className={`py-[6rem] ${className || ''}`}
            style={style}
        >
            {children}
        </section>
    )
}
