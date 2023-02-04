import {Controller} from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['content', 'content2']

    static values = {
        url: String,
        url2: String,
    }

    async refreshContent(event) {
        const response = await fetch(this.urlValue)
        this.contentTarget.innerHTML = await response.text()
    }

    async refreshContent2(event) {
        const response = await fetch(this.url2Value)
        this.content2Target.innerHTML = await response.text()
    }
}
