function Button(props) {
    return(
        <button
            onClick={props.onClick}
            className="cursor-pointer mt-2 px-4 py-3 rounded-lg bg-blue-400 text-white font-bold"
        >
            {props.children}
        </button>
    )
}

export default Button;