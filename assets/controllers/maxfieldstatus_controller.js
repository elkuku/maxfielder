import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['status', 'statusicon', 'files', 'frames', 'movie', 'log', 'actions'];

    runner

    connect() {
        const parent = this

        this.fetchStatus()

        this.runner = setInterval(function() {
            parent.fetchStatus().catch(console.log)

        }, 1000);
    }

    async fetchStatus() {
        this.statusiconTarget.classList.remove('d-none')
        const response = await fetch(this.element.dataset.fetchUrl);
        const responseText = await response.text()

        const status = JSON.parse(responseText)

        this.statusTarget.innerText = status.status;
        switch (status.status) {
            case 'running':
                this.statusTarget.classList.add('bg-warning')

                break
            case 'error':
                this.statusTarget.classList.remove('bg-warning')
                this.statusTarget.classList.add('bg-danger')

                clearInterval(this.runner)

                break

            case 'finished':
                this.statusTarget.classList.remove('bg-warning')
                this.statusTarget.classList.add('bg-success')

                clearInterval(this.runner)

                break
        }

        this.filesTarget.innerText = status.filesFinished;
        switch (status.filesFinished) {
            case false:
                this.filesTarget.classList.add('bg-warning')

                break
            case true:
                this.filesTarget.classList.remove('bg-warning')
                this.filesTarget.classList.add('bg-success')
                this.actionsTarget.classList.remove('d-none')

                break
        }

        this.framesTarget.innerText = status.framesDirCount;
        switch (status.framesDirCount) {
            case 'n/a':
                this.framesTarget.classList.add('bg-warning')

                break
            default:
                this.framesTarget.classList.remove('bg-warning')

                break
        }

        this.movieTarget.innerText = status.movieSize;
        switch (status.movieSize) {
            case 'n/a':
                this.movieTarget.classList.add('bg-warning')

                break
            default:
                this.movieTarget.classList.remove('bg-warning')

                break
        }

        this.logTarget.innerText = status.log;
        this.statusiconTarget.classList.add('d-none')

    }
}
