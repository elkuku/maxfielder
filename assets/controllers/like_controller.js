import {Controller} from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    async toggle(event) {
        const heart = event.currentTarget.children[0]

        const response = await fetch(event.params.url)
        const data = await response.json()

        if (heart.classList.contains('bi-heart-fill')) {
            heart.classList.replace('bi-heart-fill', 'bi-heart')
        } else {
            heart.classList.replace('bi-heart', 'bi-heart-fill')
        }

        this.dispatch('success');
    }
}
