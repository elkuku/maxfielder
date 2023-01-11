import {Controller} from '@hotwired/stimulus'
import { useClickOutside, useDebounce } from 'stimulus-use'

export default class extends Controller {
    static values = {
        url: String,
    }

    static targets = ['result']

    static debounces = ['search']

    connect() {
        useClickOutside(this)
        useDebounce(this)
    }

    onSearchInput(event) {
        this.search(event.currentTarget.value)
    }

    async search(query) {
        console.log(this.urlValue)
        const params = new URLSearchParams({
            q: query,
            preview: 1
        })
        const response = await fetch(`${this.urlValue}?${params.toString()}`)
        // console.log(await response.text())
        this.resultTarget.innerHTML = await response.text()
    }

    clickOutside(event) {
        console.log('clickwee outside')

        this.resultTarget.innerHTML = ''
    }
}
