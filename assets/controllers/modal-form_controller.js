import {Controller} from '@hotwired/stimulus'
import { Modal } from 'bootstrap';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['modal', 'modalBody'];

    currentUrl = ''
    modal = null

    async openModal(event) {
        console.log(event);
        this.modal = new Modal(this.modalTarget);
        this.currentUrl = event.params.url+'?partial=1'

        this.modal.show();

        const response = await fetch(this.currentUrl)
        this.modalBodyTarget.innerHTML = await response.text()
    }

    async submitForm(event) {
        console.log('submitting...', event)
        event.preventDefault();
        const form = this.modalBodyTarget.getElementsByTagName('form')[0]

        try {
            let response = await fetch(this.currentUrl, {
                method: 'POST',
                body: new FormData(form)
            });

            let result = await response.text();
            this.modal.hide();
            this.dispatch('closed')
        } catch (e) {
            this.modalBodyTarget.innerHTML = e.responseText;
        }
    }
}
