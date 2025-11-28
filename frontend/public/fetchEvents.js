async function fetchAndRender(
    dsgUrl,
    organisationUserName,
    limit = 5,
    category = [],
    afterDateTime = new Date(Date.now()),
    beforeDateTime = null
) {
    let data;
    console.log('organisationUserName', organisationUserName);
    if (organisationUserName != 'ALL') {
        data = JSON.stringify({
            operationName: 'FetchGroupEvents',
            query: `query FetchGroupEvents($name: String!, $afterDateTime: DateTime, $beforeDateTime: DateTime, $limit: Int) {
              group(preferredUsername: $name) {
                organizedEvents(
                  afterDatetime: $afterDateTime
                  beforeDatetime: $beforeDateTime
                  limit: $limit
                ) {
                  elements {
                    uuid
                    title
                    beginsOn
                    __typename
                  }
                  total
                  __typename
                }
              }
            }
    `,
            variables: {
                name: organisationUserName,
                beforeDateTime,
                afterDateTime,
                limit,
            },
        });
    } else {
        data = JSON.stringify({
            operationName: 'SearchEvents',
            query: `query SearchEvents($beginsOn: DateTime,
  $endsOn: DateTime,
  $limit: Int,
  $searchTarget: SearchTarget,
  $categoryOneOf: [String],
) {
  searchEvents(
    beginsOn: $beginsOn,
    endsOn: $endsOn,
    limit: $limit,
    searchTarget: $searchTarget,
    categoryOneOf: $categoryOneOf,
    sortBy: START_TIME_ASC
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
        }
        }
        }`,
            variables: {
                beforeDateTime,
                afterDateTime,
                limit,
                categoryOneOf: category === 'ALL' ? [] : [category],
                searchTarget: 'INTERNAL',
            },
        });
    }

    const response = await fetch(`${dsgUrl}/api`, {
        method: 'post',
        body: data,
        headers: {
            'Content-Type': 'application/json',
        },
    });

    const json = await response.json();
    target = document.getElementById('events-list');

    const shortFormat = new Intl.DateTimeFormat('de-DE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: 'numeric',
        timeZone: 'Europe/Paris',
    });

    target = document.getElementById('dsg-event-list');
    const elements = json.data?.group?.organizedEvents?.elements || json.data?.searchEvents?.elements;
    elements.forEach((event) => {
        start = shortFormat.format(new Date(event.beginsOn));
        li = document.createElement('li');

        anchor = document.createElement('a');
        anchor.setAttribute('href', `${dsgUrl}/events/${event.uuid}`);
        anchor.innerHTML = `<span class="date" style="padding-right: 2px">${start}: </span><strong>${event.title}</strong>`;

        li.appendChild(anchor);
        target.appendChild(li);
    });
}

function execute() {
    const datasets = document.getElementById('dsg-event-script').dataset;
    window.addEventListener(
        'load',
        fetchAndRender(
            datasets?.dsgUrl,
            datasets.organisationUsername,
            parseInt(datasets?.limit) || 5,
            datasets.category
        )
    );
}
execute();
