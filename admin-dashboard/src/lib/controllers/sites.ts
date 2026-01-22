import { NextResponse } from "next/server";

// Pattern: controller handles business logic, calls queries, returns response
// TODO: Implement after schema is finalized

export async function listSites() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function getSite() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function getSitePlacementsById() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function addSite() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function addPlacement() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}
