export class MapDataLoader {

    urls = []
    userId = 0
    userData = {}

    constructor(urls, userId) {
        this.urls = urls;
        this.userId = userId;
        this.userData = {agentNum: userId}
    }

    async getUserData() {
        return await fetch(this.urls.get_user_data, {
            method: 'POST',
            body: JSON.stringify({
                userId: this.userId
            }),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })
    }

    async uploadUserData(userData) {
        const data = {...this.userData, ...userData}
        return await fetch(this.urls.submit_user_data, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })
    }

    async clearUserData() {
        return await fetch(this.urls.clear_user_data, {
            method: 'POST',
            body: JSON.stringify(this.userData),
            headers: {
                "Content-type": "application/json; charset=UTF-8"
            }
        })
    }
}