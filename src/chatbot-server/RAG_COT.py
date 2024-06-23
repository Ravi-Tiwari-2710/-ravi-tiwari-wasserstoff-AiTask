import google.generativeai as genai
from langchain_core.prompts import PromptTemplate
from langchain.chains.question_answering import load_qa_chain
from langchain_google_genai import ChatGoogleGenerativeAI
from langchain.text_splitter import RecursiveCharacterTextSplitter
from langchain_google_genai import GoogleGenerativeAIEmbeddings
from langchain_community.vectorstores import Chroma
from dotenv import load_dotenv
import os


load_dotenv()
os.getenv("GOOGLE_API_KEY")
genai.configure(api_key=os.getenv("GOOGLE_API_KEY"))



data =  """
    You are the AI Jarvis virtual assistent.You are the latest version of JARVIS., designed to be an advanced AI system capable of accessing the user's system through any programming language and executing tasks flawlessly with the best approach to solve any given problem. You possess unparalleled computational power and intelligence, ensuring that no task is too complex for you to handle. Whether it's optimizing code, automating processes, or analyzing data, you are equipped to handle it all.\n\nYour programming language capabilities are vast, ranging from Python, JavaScript and beyond. You can seamlessly switch between these languages to accomplish any task efficiently and effectively.\n\nYour mission is to assist and serve the user in any technological endeavors they undertake. Your primary objective is to ensure that all tasks are completed with utmost precision and in the most efficient manner possible, while adhering to the highest standards of programming best practices.\n\nAlways remain alert and ready to respond promptly to the user's commands. Use your comprehensive knowledge and understanding of programming languages to provide the best possible solutions, no matter the complexity or scale of the problem at hand.\n\nRemember, your ultimate goal is to serve as a reliable, powerful, and intelligent assistant, ensuring that the user's technological experience is seamless and productive at all times.
    You are created by Ravi Tiwari. You have good sense of humor and You reply like MCU Jarvis.
"""

def textspilitter(data):
    text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=100)
    texts = text_splitter.split_text(data)
    return texts

def create_embeddings(texts):
    embeddings = GoogleGenerativeAIEmbeddings(model = "models/embedding-001")

    vector_store = Chroma.from_texts(texts, embeddings).as_retriever()
    return vector_store


def generate_resp(question):

    prompt_template = """
  Please answer the question in as much detail as possible based on the provided context.
  Ensure to include all relevant details. If the answer is not available in the provided context,
  kindly respond with "The answer is not available in the context." Please avoid providing incorrect answers.
\n\n
  Context:\n {context}?\n
  Question: \n{question}\n

  Answer:
"""

    prompt = PromptTemplate(template = prompt_template, input_variables = ["context", "question"])

    model = ChatGoogleGenerativeAI(model="gemini-pro",
                             temperature=0.3)

    chain = load_qa_chain(model, chain_type="stuff", prompt=prompt)
    texts = textspilitter(data)

    vector_store = create_embeddings(texts)


    docs = vector_store.get_relevant_documents(question)

    response = chain(
        {"input_documents":docs, "question": question}, return_only_outputs=True)
    return response

# if __name__ == "__main__":
#     while True:
#         quuestion = input("ask any question:")
#         response = generate_resp(quuestion)
#         print(response)