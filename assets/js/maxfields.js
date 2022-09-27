let frameNum = 0
let intervalId = 0

const jsData = $('#js-data')

const maxFrames = jsData.data('maxFrames')
const item = jsData.data('item')
const steps = jsData.data('steps')

$('.showAllLinks').click(function () {
    console.log($(this).data('agentId'))

    $('.foreign-Link-'+$(this).data('agentId')).toggle()
})

$('.sendMail').click(function () {
    const agent = this.id
    const email = $(this).prevAll('input[type=text]').val()
    const item = $('#itemId').html()

    const resultContainer = $('#result-' + agent)

    resultContainer.html('Sending email...')

    $.ajax({
        url: '/max-fields/send_mail',
        data: {
            agent: agent,
            email: email,
            item: item
        },

        success: function (result) {
            resultContainer.html(result.message)
        }
    })
})

$('#framePlus').click(function () {
    if (frameNum === maxFrames) {
        return
    }

    frameNum++
    changeImage()
})

$('#frameMinus').click(function () {
    if (frameNum === 0) {
        return
    }

    frameNum--
    changeImage()
})

$('#maxfield2strike_btn').click(function () {
    $('#maxfield2strike_form').toggle()
})

$('#maxfield2strike_form').on('submit', function (event) {
    const statusContainer = $('#maxfield2strike_status')
    const resultContainer = $('#maxfield2strike_result')

    const opName = $('input[name=opName]').val()

    statusContainer.html('Creating OP "' + opName + '"...')

    intervalId = setInterval(updateMaxfieldLog, 1000)

    $.ajax({
        url: '/max-fields/maxfield2strike?' + $(this).serialize(),

        success: function (result) {
            statusContainer.html(result.message)
        },

        error: function (xhr, status, error) {
            statusContainer.html('THERE WAS AN ERROR!')
            resultContainer.html(error)
        },
        complete: function () {
            setTimeout(function () {
                clearInterval(intervalId)
            }, (3000))
        }
    })

    event.preventDefault()
})

function updateMaxfieldLog() {
    const resultContainer = $('#maxfield2strike_result')

    $.ajax({
        url: '/max-fields/log',

        success: function (result) {
            resultContainer.html(result)
        },

        error: function (xhr, status, error) {
            resultContainer.html(error)
        }
    })
}

function changeImage() {
    $('#frameNum').html(frameNum + ' / ' + maxFrames)

    let num
    let msg = ''

    let s = '000000000' + frameNum
    num = s.substr(s.length - 5)
    // if (frameNum === -1) {
    //     num = -1
    // } else {
    //     let s = '000000000' + frameNum
    //     num = s.substr(s.length - 5)
    // }

    $('#displayFrames').attr('src', '/maxfields/' + item + '/frames/frame_' + num + '.png')

    if (0 === frameNum) {
        msg = 'Initial'
    } else if (frameNum <= maxFrames) {
        let index = frameNum - 1

        if (frameNum > 1) {
            msg += getEventLine(steps[index - 1], false)
        }

        msg += getEventLine(steps[index], true)

        if (frameNum + 1 <= maxFrames) {
            msg += getEventLine(steps[index + 1], false)
        }
    } else {
        msg = 'Final'
    }

    $('#frameLinkInfo').html(msg)
}

function getEventLine(event, isCurrent) {
    console.log(event)
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

$(function () {
    changeImage()
})
