const add_plus_one_form = ( element, id ) => {
	const form = document.getElementById( 'add_plus_one_form_' + id );
	const rsvp_yes = document.getElementById( 'rsvp_' + id + '_yes');
	const rsvp_no = document.getElementById( 'rsvp_' + id + '_no');
	const form_inputs = form.querySelectorAll( 'input' );
	form_inputs.forEach( element => {
		element.required = true;
	} );

	rsvp_yes.checked = true;

	form.classList.remove( 'hidden' );
	element.classList.add( 'hidden' );
}

const init = () => {
	console.log( 'RSVP Script Init' );
};

document.addEventListener( 'DOMContentLoaded', init );