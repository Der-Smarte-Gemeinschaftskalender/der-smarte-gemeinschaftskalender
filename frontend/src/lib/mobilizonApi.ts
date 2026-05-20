import { ApolloClient, createHttpLink, InMemoryCache, type FetchPolicy } from '@apollo/client/core'
import { checkLogin } from '@/composables/UserComposoable'

// HTTP connection to the API
const httpLink = createHttpLink({
  // You should use an absolute URL here
  uri: import.meta.env.VITE_MOBILIZON_API_URL,
})

// Cache implementation
const cache = new InMemoryCache()

export const getFetchPolicy = (): FetchPolicy => {
  return checkLogin() ? 'network-only' : 'cache-first'
}

export const apolloClient = new ApolloClient({
  link: httpLink,
  cache,
  defaultOptions: {
    watchQuery: {
      fetchPolicy: 'cache-and-network',
      nextFetchPolicy() {
        return getFetchPolicy()
      }
    },
    query: {
      fetchPolicy: 'cache-first'
    }
  }
})