/**
 * A VERY opinionated Mapbox API
 *
 * Probably not for You ;)
 */
export class MapboxAPI {
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
        if (false === response.ok) {
            if (response.status === 404) throw new Error('404, Not found');
            if (response.status === 500) throw new Error('500, internal server error');
            throw new Error(response.status);
        }
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
