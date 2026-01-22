import { NextResponse } from "next/server";

// Pattern: controller handles business logic, calls queries, returns response
// TODO: Implement after schema is finalized

export async function listAdvertisers() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function getAdvertiser() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function pauseAdvertiser() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function activateAdvertiser() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}
