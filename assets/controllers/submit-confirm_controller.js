import {Controller} from '@hotwired/stimulus'
import Swal from 'sweetalert2'

export default class extends Controller {
    static values = {
        title: String,
        text: String,
        icon: String,
        confirmButtonText: String,
        submitAsync: Boolean,
    }

    onSubmit(event) {
        event.preventDefault()

        Swal.fire({
            title: this.titleValue || null,
            text: this.textValue || null,
            icon: this.iconValue || null,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: this.confirmButtonTextValue || 'Yes',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return this.submitForm(event)
            }
        })
    }

    async submitForm(event) {
        if (!this.submitAsyncValue) {
            window.location.replace(event.params.url)
            return
        }
        const response = await fetch(event.params.url)
        this.dispatch('async:submitted', {detail: {response}})
    }
}
