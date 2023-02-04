import {Controller} from '@hotwired/stimulus'

import '../styles/maxfield/view.css'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        item: String,
        maxFrames: Number,
        frameNum: Number,
        steps: Array,
    }

    static targets = ['frameNum', 'framesImage', 'frameLinkInfo', 'btnShowForeign', 'tab', 'tabBtn']

    displayForeign = true

    connect() {
        this.changeFrame()

        // const triggerTabList = [].slice.call(document.querySelectorAll('#accordionExample button'));
        // triggerTabList.forEach(function (triggerEl) {
        //     const tabTrigger = new Tab(triggerEl);
        //
        //     triggerEl.addEventListener('click', function (event) {
        //         event.preventDefault()
        //         tabTrigger.show()
        //     })
        // })
    }

    framePlus() {
        if (this.frameNumValue === this.maxFramesValue) {
            return
        }

        this.frameNumValue++
        this.changeFrame()
    }

    frameMinus() {
        if (this.frameNumValue === 0) {
            return
        }

        this.frameNumValue--
        this.changeFrame()
    }

    changeFrame() {
        const s = '000000000' + this.frameNumValue
        const num = s.substring(s.length - 5)
        let msg = ''

        this.framesImageTarget.src = '/maxfields/' + this.itemValue + '/frames/frame_' + num + '.png'
        this.frameNumTarget.innerText = this.frameNumValue + ' / ' + this.maxFramesValue

        if (0 === this.frameNumValue) {
            msg = 'Initial'
        } else if (this.frameNumValue <= this.maxFramesValue) {
            let index = this.frameNumValue - 1

            if (this.frameNumValue > 1) {
                msg += this.getEventLine(this.stepsValue[index - 1], false)
            }

            msg += this.getEventLine(this.stepsValue[index], true)

            if (this.frameNumValue + 1 <= this.maxFramesValue) {
                msg += this.getEventLine(this.stepsValue[index + 1], false)
            }
        } else {
            msg = 'Final'
        }

        this.frameLinkInfoTarget.innerHTML = msg
    }

    getEventLine(event, isCurrent) {
        let css = isCurrent ? 'linkCurrent' : 'link'
        let cssMsg = event.action === 1 ? 'msgLink' : 'msgMove'
        let msg = event.action === 1 ? 'Link' : 'Move'
        let num = event.linkNum > 0 ? event.linkNum : ''

        return '<div class="' + css + '">'
            + '<span class="' + cssMsg + '"> ' + msg + ' </span> ' + num + ' - agent: ' + event.agentNum
            + ' - ' + event.originName + ' (' + event.originNum + ')'
            + ' &rArr; ' + event.destinationName + ' (' + event.destinationNum + ')'
            + '</div>'
    }

    toggleShowForeign() {
        this.displayForeign = !this.displayForeign
        for (let btn of this.btnShowForeignTargets) {
            btn.innerText = this.displayForeign ? 'Hide foreign' : 'Show foreign'
        }
        for (let link of document.getElementsByClassName('foreign-link')) {
            link.style.display = this.displayForeign ? '' : 'none'
        }
    }

    showTab(event) {
        for (let tab of this.tabTargets) {
            tab.style.display = (tab.dataset.id === event.params.tab) ? '' : 'none'
        }
        for (let btn of this.tabBtnTargets) {
            if (btn.dataset.id === event.params.tab) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active')
            }
        }
    }
}
