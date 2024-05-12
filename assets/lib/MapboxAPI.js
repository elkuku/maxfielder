/**
 * A VERY opinionated Mapbox API
 *
 * Probably not for You ;)
 */
module.exports = class MapboxAPI {
    baseUrl = 'https://api.mapbox.com'

    constructor(token) {
        this.token = token;
    }

    async getRoute(start, end, profile) {
        const coordinates = [start, end]
        const response = await fetch(
            this.baseUrl + '/directions/v5/'
            + profile + '/'
            + coordinates.join(';')
            + '?steps=true&geometries=geojson'
            + '&access_token=' + this.token
            , {method: 'GET'}
        )

        const json = await response.json()
        return json.routes[0]
    }

    async getOptimizedRoute(points, profile) {
        let coordinates = []
        for (const point of points) {
            coordinates.push(point)
        }

        const response = await fetch(
            this.baseUrl + '/optimized-trips/v1/'
            + profile + '/'
            + coordinates.join(';') +
            '?overview=full&steps=true&geometries=geojson' +
            '&roundtrip=false' +
            '&source=first&destination=last' +
            '&access_token=' + this.token
            , {method: 'GET'}
        )

        return await response.json()
    }
}