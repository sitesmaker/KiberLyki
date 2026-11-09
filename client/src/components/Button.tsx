import type { ComponentPropsWithoutRef } from 'react';

type ButtonProps = ComponentPropsWithoutRef<'button'>;

function Button({ className = '', ...props }: ButtonProps) {
    return(
        <button
            {...props}
            className={`club-button ${className}`}
        />
    )
}

export default Button;
