import {Controller} from '@hotwired/stimulus'

export default class extends Controller {
    static targets = [
        'heart'
    ]
    static values = {
        toggleUrl: String
    }

    async toggle() {
        const response = await fetch(this.toggleUrlValue)
        const data = await response.json()
        console.log(data)

        if (this.heartTarget.classList.contains('bi-heart-fill')) {
            this.heartTarget.classList.replace('bi-heart-fill', 'bi-heart')
        } else {
            this.heartTarget.classList.replace('bi-heart', 'bi-heart-fill')
        }
        this.dispatch('success');
    }
}
