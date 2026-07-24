export type ListQueryParams = {
  page?: number;
  perPage?: number;
  search?: string;
  sort?: string;
  updatedAfter?: string | null;
};

export function buildListQuery(params: ListQueryParams = {}): string {
  const query = new URLSearchParams();

  if (params.page) {
    query.set('page', String(params.page));
  }

  if (params.perPage) {
    query.set('per_page', String(params.perPage));
  }

  if (params.search?.trim()) {
    query.set('search', params.search.trim());
  }

  if (params.sort) {
    query.set('sort', params.sort);
  }

  if (params.updatedAfter) {
    query.set('filter[updated_after]', params.updatedAfter);
  }

  const serialized = query.toString();

  return serialized ? `?${serialized}` : '';
}

export async function fetchAllPages<T>(
  fetchPage: (page: number) => Promise<{
    items: T[];
    lastPage: number;
  }>,
): Promise<T[]> {
  const items: T[] = [];
  let page = 1;
  let lastPage = 1;

  do {
    const response = await fetchPage(page);
    items.push(...response.items);
    lastPage = response.lastPage;
    page += 1;
  } while (page <= lastPage);

  return items;
}
