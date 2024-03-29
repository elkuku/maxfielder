import {Controller} from '@hotwired/stimulus'
import { useClickOutside, useDebounce } from 'stimulus-use'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        url: String,
    }

    static targets = ['result']

    static debounces = ['search']
    query = ''

    connect() {
        useClickOutside(this)
        useDebounce(this)
    }

    onSearchInput(event) {
        this.query = event.currentTarget.value
        this.search()
    }

    async search() {
        const params = new URLSearchParams({
            q: this.query,
            partial: 'searchPreview'
        })
        const response = await fetch(`${this.urlValue}?${params.toString()}`)
        this.resultTarget.innerHTML = await response.text()
    }

    clickOutside(event) {
        this.resultTarget.innerHTML = ''
    }
}
