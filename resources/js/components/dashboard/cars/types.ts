export type EnumOption = { value: string; label: string };

export type NamedOption = { id: number; name: string };

export type CarFormOptions = {
    brands: NamedOption[];
    categories: NamedOption[];
    showrooms: (NamedOption & { city: string })[];
    fuelTypes: EnumOption[];
    transmissions: EnumOption[];
    conditions: EnumOption[];
    statuses: EnumOption[];
    badges: EnumOption[];
    optionGroups: EnumOption[];
};

export type CarTab =
    | 'info'
    | 'specs'
    | 'colors'
    | 'options'
    | 'images'
    | 'hotspots';

export type CarImage = {
    id: number;
    path: string;
    sort_order: number;
    is_cover: boolean;
    url: string;
};

export type CarColor = {
    id: number;
    name: string;
    hex: string;
    price_delta: number;
    image_path: string | null;
    image_url: string | null;
};

export type CarOption = {
    id: number;
    group: string;
    name: string;
    description: string | null;
    price: number;
    image_path: string | null;
    image_url: string | null;
};

export type CarHotspot = {
    x: number;
    y: number;
    title: string;
    description: string;
};

export type CarDetail = {
    id: number;
    brand_id: number;
    category_id: number;
    showroom_id: number | null;
    name: string;
    slug: string;
    year: number;
    base_price: number;
    fuel_type: string;
    transmission: string;
    engine: string;
    power_hp: number;
    torque_nm: number;
    accel_0_100: string | null;
    top_speed: number;
    range_km: number | null;
    seats: number;
    mileage_km: number;
    condition: string;
    stock: number;
    badge: string | null;
    status: string;
    description: string | null;
    hotspots: CarHotspot[] | null;
    is_featured: boolean;
    brand: NamedOption;
    images: CarImage[];
    colors: CarColor[];
    options: CarOption[];
};
