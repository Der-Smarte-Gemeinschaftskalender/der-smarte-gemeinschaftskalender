import gql from 'graphql-tag';
import { apolloClient } from '@/lib/mobilizonApi';

import type { IEvent, IEventDetailed, IOrganisation, MobilizonStatistics } from '@/types/General';
import type { AddressForm } from '@/types/Mobilizon';


export const findNextEvents = async (limit: number = 5, beginsOn: Date | null = null, endsOn: Date | null = null): Promise<{
  __typename: string,
  elements: IEvent[],
  total: number
}> => {
  const response = await apolloClient.query({
    query: gql`
                query SearchEvents($limit: Int!, $sortBy: SearchEventSortOptions!, $beginsOn: DateTime, $endsOn: DateTime) {
                    searchEvents(limit: $limit, sortBy: $sortBy, beginsOn: $beginsOn, endsOn: $endsOn) {
                        elements {
                            beginsOn
                            category
                            endsOn
                            status
                            title
                            url
                            uuid
                            picture {
                              url
                              name
                              __typename
                            }
                            attributedTo {
                                domain
                                id
                                name
                            }
                        }
                        total
                    }
                }
            `,
    variables: {
      limit,
      sortBy: 'START_TIME_ASC',
      beginsOn,
      endsOn
    },
  });

  return response?.data?.searchEvents;
}

export const findEvent = async (uuid: string): Promise<IEventDetailed> => {
  const response = await apolloClient.query({
    query: gql`
            query Event($uuid: UUID!) {
    event(uuid: $uuid) {
        beginsOn
        category
        description
        draft
        endsOn
        externalParticipationUrl
        id
        insertedAt
        joinOptions
        language
        local
        onlineAddress
        phoneAddress
        publishAt
        slug
        status
        title
        updatedAt
        url
        uuid
        visibility
        physicalAddress {
            country
            description
            geom
            id
            locality
            originId
            postalCode
            region
            street
            timezone
            type
            url
        }
        relatedEvents {
            beginsOn
            category
            description
            draft
            endsOn
            externalParticipationUrl
            id
            insertedAt
            joinOptions
            language
            local
            onlineAddress
            phoneAddress
            publishAt
            slug
            status
            title
            updatedAt
            url
            uuid
            visibility
                                    picture {
                          url
                          name
                          __typename
                        }
        }
        tags {
                id
                slug
                title
        }
        picture {
            alt
            contentType
            name
            size
            url
        }
        attributedTo {
            domain
            followersCount
            followingCount
            id
            local
            manuallyApprovesFollowers
            mediaSize
            name
            preferredUsername
            summary
            suspended
            type
            url
            avatar {
                alt
                contentType
                name
                size
                url
            }
        }
    }
}

        `,
    variables: {
      uuid
    },
    errorPolicy: "ignore"
  });
  return response?.data?.event;
}

export const searchEvents = async (page = 1, limit: number | null = null, term = '', searchTarget: string | null = null, beginsOn: string = new Date().toISOString(), endsOn: string | null = null, categoryOneOf: string[] | null = null, statusOneOf: string[] | null = null, languageOneOf: string[] | null = null, location: string | null = null, radius: number | null = null, fields: string = ''): Promise<{
    searchEvents: any,
    total: number
}> => {
  const response = await apolloClient.query({
    query: gql`
            query SearchEvents(
  $location: String
  $radius: Float
  $tags: String
  $term: String
  $type: EventType
  $beginsOn: DateTime
  $endsOn: DateTime
  $limit: Int
  $searchTarget: SearchTarget
  $categoryOneOf: [String]
  $statusOneOf: [EventStatus]
  $languageOneOf: [String]
  $page: Int
) {
  searchEvents(
    location: $location
    radius: $radius
    tags: $tags
    term: $term
    type: $type
    beginsOn: $beginsOn
    endsOn: $endsOn
    limit: $limit
    searchTarget: $searchTarget
    sortBy: START_TIME_ASC
    categoryOneOf: $categoryOneOf
    statusOneOf: $statusOneOf
    languageOneOf: $languageOneOf
    page: $page
  ) {
    total
    elements {
      id
      title
      uuid
      status
      category
      beginsOn
      endsOn
      
      ${!!fields ? '... on Event {' + fields + '}' : ''}
  
        attributedTo {
            name
            preferredUsername
            summary
        }

        physicalAddress {
            country
            geom
            locality
            postalCode
            street
        }
      picture {
        url
        name
        __typename
      }
      status
      tags {
        ...TagFragment
        __typename
      }
      physicalAddress {
        ...AdressFragment
        __typename
      }
      organizerActor {
        ...ActorFragment
        __typename
      }
      attributedTo {
        ...ActorFragment
        __typename
      }
      options {
        ...EventOptions
        __typename
      }
      __typename
    }
    __typename
  }
}

fragment EventOptions on EventOptions {
  maximumAttendeeCapacity
  remainingAttendeeCapacity
  showRemainingAttendeeCapacity
  anonymousParticipation
  showStartTime
  showEndTime
  timezone
  offers {
    price
    priceCurrency
    url
    __typename
  }
  participationConditions {
    title
    content
    url
    __typename
  }
  attendees
  program
  commentModeration
  showParticipationPrice
  hideOrganizerWhenGroupEvent
  isOnline
  __typename
}

fragment TagFragment on Tag {
  id
  slug
  title
  __typename
}

fragment AdressFragment on Address {
  id
  description
  geom
  street
  locality
  postalCode
  region
  country
  type
  url
  originId
  timezone
  __typename
}

fragment ActorFragment on Actor {
  id
  avatar {
    url
    __typename
  }
  type
  preferredUsername
  name
  domain
  summary
  url
  __typename
}
        `,
    variables: {
      limit,
      term,
      //searchTarget,
      beginsOn,
      endsOn,
      longEvents: true,
      sortBy: 'START_TIME_ASC',
      page,
      categoryOneOf,
      statusOneOf,
      languageOneOf,
      location: location,
      radius,
    },
  });
  return response?.data;

}

export const findMobilizonStatistics = async (): Promise<MobilizonStatistics | undefined> => {
  const response = await apolloClient.query({
    query: gql`
            query Statistics {
                statistics {
                    numberOfComments
                    numberOfEvents
                    numberOfGroups
                    numberOfInstanceFollowers
                    numberOfInstanceFollowings
                    numberOfLocalComments
                    numberOfLocalEvents
                    numberOfLocalGroups
                    numberOfUsers
                }
            }`,
  });

  return response?.data?.statistics as MobilizonStatistics;
}

export const findOrganisations = async (limit = 50): Promise<{ elements: IOrganisation[], total: number }> => {
  const response = await apolloClient.query({
    query: gql`
            query SearchGroups($limit: Int) {
    searchGroups(searchTarget: INTERNAL, limit: $limit) {
        total
        elements {
            domain
            id
            name
            preferredUsername
            summary
            type
            url
            avatar {
                alt
                contentType
                name
                size
                url
            }
            physicalAddress {
                country
                description
                geom
                id
                locality
                postalCode
                region
                street
            }
        }
    }
  }

        `,
    variables: {
      limit,
    },
  });

  return response?.data?.searchGroups || { elements: [], total: 0 };
}

export const findOrganisation = async (preferredUsername: string, organizedEvents: any, fields: string = '') => {
  try {
    const response = await apolloClient.query({
      query: gql`
            query Group ($preferredUsername: String!, $organizedEventsAfterDateTime: DateTime) {
  group(preferredUsername: $preferredUsername) {
    domain
    id
    local
    name
    openness
    preferredUsername
    summary
    suspended
    url
    physicalAddress {
      country
      description
      locality
      postalCode
      street
      type
      geom
    }
    avatar {
       alt
       contentType
       name
       size
       url
    }
    visibility
    organizedEvents(limit: 12, afterDatetime: $organizedEventsAfterDateTime, order: BEGINS_ON) {
      total
            elements {
        beginsOn
        endsOn
        title
        id
        slug
        status
        uuid
        ${fields}
                picture {
                          url
                          name
                          __typename
                        }
      }
    }
  }
}

        `,
      variables: {
        preferredUsername,
        organizedEventsAfterDateTime: organizedEvents.afterDateTime ? organizedEvents.afterDateTime : new Date().toISOString(),
      },
    });
    return response?.data?.group;
  } catch (error: any) {
    throw new Error(error)
  }
}

export const findOrganisationOptions = async (valueUsername = false, limit: number = 1000) => {
  const response = await apolloClient.query({
    query: gql`
            query SearchGroups($limit: Int) {
              searchGroups(searchTarget: INTERNAL, limit: $limit) {
                elements {
                  id
                  name
                  preferredUsername
                }
              }
            }
        `, variables: { limit }
  });
  const groupOptions = response?.data?.searchGroups?.elements?.map((group: any) => {
    return {
      value: valueUsername ? group.preferredUsername : group.id,
      text: group.name
    };
  })
  return [
    { value: 'ALL', text: 'Alle Organisationen' },
    ...groupOptions
  ]
}


export const fetchOrganisationEvents = async (name: string, fromDate: string | null = null, afterDate: string | null = null, limit: number | null = null) => {
  const response = await apolloClient.query({
    query: gql`
            query FetchGroupEvents($name: String!, $fromDate: DateTime, $afterDate: DateTime, $organisedEventsLimit: Int) {
  group(preferredUsername: $name) {
      name,
      avatar {
            contentType
            size
            url
      }
      physicalAddress {
            country
            description
            locality
            postalCode
            street
            type
        }
    organizedEvents(
      afterDatetime: $fromDate
      beforeDatetime: $afterDate
      limit: $organisedEventsLimit
    ) {
      elements {
        id
        uuid
        title
        beginsOn
        endsOn
        status
        draft
      }
      total
      __typename
    }
    __typename
  }
}
        `,
    variables: {
      name,
      fromDate: fromDate ?? new Date().toISOString(),
      afterDate: afterDate ?? null,
      organisedEventsLimit: limit ?? null,
    },
  });
  return {
    name: response?.data?.group?.name,
    physicalAddress: response?.data?.group?.physicalAddress,
    elements: response?.data?.group?.organizedEvents?.elements ?? [],
    total: response?.data?.group?.organizedEvents?.total ?? 0,
    avatar: response?.data?.group?.avatar ?? null
  };
}

export const searchAddress = async (query: string, limit: number = 10) => {
  const response = await apolloClient.query({
    query: gql`
            query SearchAddress ($query: String!, $limit: Int) {
    searchAddress(query: $query, limit: $limit) {
        country
        description
        geom
        id
        locality
        originId
        postalCode
        region
        street
        timezone
        type
        url
    }
}

        `,
    variables: {
      query,
      limit
    },
  });

  return response?.data?.searchAddress;
}

export const extendedSearchAddress = async (
  query: string,
  locale: string,
  type: string,
  limit: number = 10,
): Promise<AddressForm[]> => {
  const response = await apolloClient.query({
    query: gql`
      query SearchAddress(
        $query: String!
        $limit: Int
        $locale: String
        $type: AddressSearchType
      ) {
        searchAddress(
          query: $query
          limit: $limit
          locale: $locale
          type: $type
        ) {
          id
          description
          geom
          street
          locality
          postalCode
          region
          country
          type
          url
          originId
          timezone
          pictureInfo {
            url
            author {
              name
              url
              __typename
            }
            source {
              name
              url
              __typename
            }
            __typename
          }
          __typename
        }
      }
    `,
    variables: {
      query,
      limit,
      locale,
      type,
    },
  });

  return (response.data.searchAddress || []) as AddressForm[];
};

export const getAbout = async (): Promise<object> => {
  const response = await apolloClient.query({
    query: gql`
      query about {
          config {
            longDescription
            contact
            registrationsOpen
        }
      }
    `,
  });

  return response.data.config || {}
}
export const getTerms = async (): Promise<object> => {
  const response = await apolloClient.query({
    query: gql`
      query Terms($locale: String) {
        config {
          terms(locale: $locale) {
            type
            url
            bodyHtml
            __typename
          }
          __typename
        }
      }
    `,
    variables: {
      locale: "de",
    },
  });
  return response.data.config.terms || {}
}
export const getPrivacy = async (): Promise<object> => {
  const response = await apolloClient.query({
    query: gql`
      query Privacy($locale: String) {
        config {
          privacy(locale: $locale) {
            type
            url
            bodyHtml
            __typename
          }
          __typename
        }
      }
    `,
    variables: {
      locale: "de",
    },
  });
  return response.data.config.privacy || {}
}
