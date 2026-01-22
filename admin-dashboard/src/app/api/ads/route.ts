import { listAds } from "@/lib/controllers/ads";

// Pattern: routes are thin - they just delegate to controllers
export const GET = listAds;

// TODO: Add PATCH handler after controllers are implemented
