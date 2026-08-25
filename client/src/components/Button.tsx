import type { ComponentPropsWithoutRef } from 'react';

type ButtonProps = ComponentPropsWithoutRef<'button'>;

function Button({ className = '', ...props }: ButtonProps) {
    return(
        <button
            {...props}
            className={`cursor-pointer mt-2 px-4 py-3 rounded-lg bg-blue-400 text-white font-bold disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
        />
    )
}

export default Button;
