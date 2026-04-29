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
    storageKeys = 'maxfield-keys'
    storageAgents = 'maxfield-agents'

    getStorageId() {
        return this.itemValue
    }

    connect() {
        this.changeFrame()
        this.loadFromStorage()
    }

    loadFromStorage() {
        // Load keys
        const keysData = sessionStorage.getItem(this.storageKeys + '-' + this.getStorageId())
        if (keysData) {
            const keys = JSON.parse(keysData)
            for (const [guid, count] of Object.entries(keys)) {
                const input = document.querySelector(`input[data-guid="${guid}"]`)
                if (input) {
                    input.value = count
                    // Update faltan
                    const row = input.closest('tr')
                    const needed = parseInt(row.dataset.keys)
                    const faltan = Math.max(0, needed - count)
                    row.querySelector('[data-faltan]').textContent = faltan
                }
            }
        }

        // Load agent names
        const agentsData = sessionStorage.getItem(this.storageAgents + '-' + this.getStorageId())
        if (agentsData) {
            const agents = JSON.parse(agentsData)
            for (const [agentNum, name] of Object.entries(agents)) {
                const input = document.querySelector(`input[data-agent-num="${agentNum}"]`)
                const navLink = document.querySelector(`.nav-link[data-id="agent-${agentNum}"]`)
                if (input) input.value = name
                if (navLink) navLink.textContent = name
            }
        }
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

    frameStart() {
        this.frameNumValue = 0
        this.changeFrame()
    }

    frameEnd() {
        this.frameNumValue = this.maxFramesValue
        this.changeFrame()
    }

    changeFrame() {
        const s = '000000000' + this.frameNumValue
        const num = s.substring(s.length - 5)
        let msg = ''

        this.framesImageTarget.src = '/maxfields/' + this.itemValue + '/frames/frame_' + num + '.gif'
        this.frameNumTarget.innerText = (this.frameNumValue + 1) + ' / ' + (this.maxFramesValue + 1)

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

    getAgentName(agentNum) {
        const storageKey = this.storageAgents + '-' + this.getStorageId()
        const agentsData = sessionStorage.getItem(storageKey)
        if (agentsData) {
            const agents = JSON.parse(agentsData)
            if (agents[agentNum]) {
                return agents[agentNum]
            }
        }
        return 'Agent ' + agentNum
    }

    getEventLine(event, isCurrent) {
        let css = isCurrent ? 'linkCurrent' : 'link'
        let cssMsg = event.action === 1 ? 'msgLink' : 'msgMove'
        let msg = event.action === 1 ? 'Link' : 'Move'
        let num = event.linkNum > 0 ? event.linkNum : ''
        const agentName = this.getAgentName(event.agentNum)

        return '<div class="' + css + '">'
            + '<span class="' + cssMsg + '"> ' + msg + ' </span> ' + num + ' - ' + agentName
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

    sortKeys(event) {
        const sortType = event.target.value
        const table = event.target.closest('.col').querySelector('table')
        const header = table.querySelector('tr')
        const rows = Array.from(table.querySelectorAll('tr[data-map-no]'))

        rows.sort((a, b) => {
            const aMapNo = parseInt(a.dataset.mapNo)
            const bMapNo = parseInt(b.dataset.mapNo)
            const aKeys = parseInt(a.dataset.keys)
            const bKeys = parseInt(b.dataset.keys)
            const aName = a.dataset.name
            const bName = b.dataset.name

            switch (sortType) {
                case 'keysAsc':
                    return aKeys - bKeys
                case 'keysDesc':
                    return bKeys - aKeys
                case 'nameAsc':
                    return aName.localeCompare(bName)
                case 'nameDesc':
                    return bName.localeCompare(aName)
                default: // mapNo
                    return aMapNo - bMapNo
            }
        })

        // Remove all data rows, keep header
        rows.forEach(row => row.remove())
        // Reappend in new order after header
        rows.forEach(row => table.appendChild(row))
    }

    sortKeysAgent(event) {
        const sortType = event.target.value
        const agent = event.target.dataset.agent
        const table = document.querySelector(`table[data-agent-table="${agent}"]`)
        if (!table) return

        const header = table.querySelector('tr')
        const rows = Array.from(table.querySelectorAll('tr[data-map-no]'))

        rows.sort((a, b) => {
            const aMapNo = parseInt(a.dataset.mapNo)
            const bMapNo = parseInt(b.dataset.mapNo)
            const aKeys = parseInt(a.dataset.keys) || 0
            const bKeys = parseInt(b.dataset.keys) || 0
            const aName = a.dataset.name
            const bName = b.dataset.name

            switch (sortType) {
                case 'keysAsc':
                    return aKeys - bKeys
                case 'keysDesc':
                    return bKeys - aKeys
                case 'nameAsc':
                    return aName.localeCompare(bName)
                case 'nameDesc':
                    return bName.localeCompare(aName)
                default: // mapNo
                    return aMapNo - bMapNo
            }
        })

        rows.forEach(row => row.remove())
        rows.forEach(row => table.appendChild(row))
    }

    updateMyKeys(event) {
        const input = event.target
        const row = input.closest('tr')
        const needed = parseInt(row.dataset.keys)
        const have = parseInt(input.value) || 0
        const faltan = Math.max(0, needed - have)
        row.querySelector('[data-faltan]').textContent = faltan

        // Save to sessionStorage
        const guid = input.dataset.guid
        const storageKey = this.storageKeys + '-' + this.getStorageId()
        let keysData = {}
        const existing = sessionStorage.getItem(storageKey)
        if (existing) keysData = JSON.parse(existing)
        keysData[guid] = have
        sessionStorage.setItem(storageKey, JSON.stringify(keysData))
    }

    updateAgentName(event) {
        const input = event.target
        const agentNum = input.dataset.agentNum
        const name = input.value || 'Agent ' + agentNum
        
        // Update nav link text
        const navLink = document.querySelector(`.nav-link[data-id="agent-${agentNum}"]`)
        if (navLink) {
            navLink.textContent = name
        }

        // Save to sessionStorage
        const storageKey = this.storageAgents + '-' + this.getStorageId()
        let agentsData = {}
        const existing = sessionStorage.getItem(storageKey)
        if (existing) agentsData = JSON.parse(existing)
        agentsData[agentNum] = name
        sessionStorage.setItem(storageKey, JSON.stringify(agentsData))
    }
}
