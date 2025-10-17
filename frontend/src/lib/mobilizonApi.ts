import { ApolloClient, createHttpLink, InMemoryCache } from '@apollo/client/core'
import { checkLogin } from '@/composables/UserComposoable'
// HTTP connection to the API
const httpLink = createHttpLink({
  // You should use an absolute URL here
  uri: import.meta.env.VITE_MOBILIZON_API_URL,
})

// Cache implementation
const cache = new InMemoryCache({
  resultCaching: checkLogin() ? false : true
})

// Create the apollo client
export const apolloClient = new ApolloClient({
  link: httpLink,
  cache
})