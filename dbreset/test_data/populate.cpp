#include <iostream>
#include <fstream>
#include <string>
#include <stdlib.h>
using namespace std;

int main() {
	string instName [] = {"Trumpet", "Trombone", "Tenor sax", "Alto sax", "Piccolo", "Clarinet", "Sousaphone", "Baritone", "Mellophone"};
	string condition [] = {"Excellent", "Good", "Fair", "Poor", "Broken"};
	string firstName [] = {"Bob", "Bill", "Gina", "Jennifer", "Tom", "George", "Chris", "Caroline", "Rachel", "Jill"};
	string lastName [] = {"Smith", "Vasquez", "Todd", "Adams", "Willis", "Green", "Doofenshmirtz", "Hill"};
	string role [] = {"student", "student", "student", "student", "manager", "manager", "admin"};
	string boolean [] = {"true", "false"};
	ofstream students;
	int cuid = 1000000;
	int serialNo = 1000;
	ofstream instruments;
	students.open("student_big_data.csv");
	instruments.open("instruments_big_data.csv");
	for(int i = 0; i < 150; i++)
	{
		string fName = firstName[rand()%10];
		string lName = lastName[rand()%8];
		string inst = instName[rand()%9];
		string cond = condition[rand()%5];
		students << cuid++ <<   "," << fName + lName << "," << 
			lName + fName << "," << role[rand()%7] << "," << 
			fName + lName + "@g.clemson.edu" << "," << 
			fName << "," << lName << endl;
		instruments << serialNo++ <<  "," << inst << "," << cond << 
				endl; 
	}
	ofstream rentalCont;
	rentalCont.open("rental_contracts_big_data.csv");
	for(int i = 0; i < 150; i++)
	{
		int startYear = (rand()%100) + 1985;
		int endYear = (rand()%100) + 1985;
		int startMonth = (rand()%12) + 1;
		int endMonth = (rand()%12) + 1;
		int startDay = (rand()%28) + 1;
		int endDay = (rand()%28) + 1;
		while(startYear > endYear)
		{
			endYear++;
		}
		if(startYear == endYear)
		{
			while(startMonth > endMonth)
			{
				endMonth++;
				if(endMonth > 12)
				{
					endYear++;
					endMonth -= 12;
				}
			}
			if(startMonth == endMonth)
			{
				while(startDay > endDay)
				{
					if(endDay < 28)
					{
						endDay++;
					}
					else
					{
						startDay--;
					}
				}
			}
		}
		rentalCont << startYear << "-" << startMonth << "-" << startDay
			<< "," << endYear << "-" << endMonth << "-" << endDay
			<< "," << (rand()%150) + 1000000 << "," 
			<< (rand()%150) + 1000 << "," << boolean[rand()%2] << endl; 
	} 
	students.close();
	instruments.close();
	rentalCont.close();
}
