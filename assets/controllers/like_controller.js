import {Controller} from '@hotwired/stimulus'

export default class extends Controller {
    static targets = [
        'heart'
    ]
    static values = {
        toggleUrl: String
    }

    connect() {
        // console.log(this.toggleUrlValue)
    }

    toggle() {
        fetch(this.toggleUrlValue)
            .then((response) => response.json())
            .then((data) => console.log(data))

        // @todo more stuff here...

        if (this.heartTarget.classList.contains('bi-heart-fill')) {
            this.heartTarget.classList.replace('bi-heart-fill', 'bi-heart')
        } else {
            this.heartTarget.classList.replace('bi-heart', 'bi-heart-fill')
        }
        // console.log(this.itemIdValue)

    }
}
